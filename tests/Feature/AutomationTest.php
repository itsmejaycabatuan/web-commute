<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Driver;
use App\Models\MaintenanceTask;
use App\Models\PreventiveMaintenance;
use App\Models\TimeKeeping;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleLocation;
use App\Models\ViolationCode;
use App\Models\ViolationLog;
use App\Notifications\DriverInactivityWarning;
use App\Notifications\MaintenanceDueDigest;
use Database\Seeders\ViolationCodeSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The jobs that used to be done by hand: the PUJ violation schedule, auto clock-out, the next
 * offense number, and the maintenance due alerts.
 */
class AutomationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'commuter', 'driver', 'driver_manager', 'maintenance_manager'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function staff(string $role): User
    {
        return User::factory()->create(['email_verified_at' => now()])->assignRole($role);
    }

    /** @return array{0: User, 1: Driver} */
    private function driver(string $status = 'active'): array
    {
        $user = User::factory()->create(['email_verified_at' => now()])->assignRole('driver');
        $driver = Driver::factory()->approved()->create(['user_id' => $user->id, 'status' => $status, 'is_suspended' => false]);

        return [$user->fresh(), $driver];
    }

    // ── Violation code seeder ────────────────────────────────────────────

    public function test_seeder_replaces_the_violation_codes_with_the_puj_schedule(): void
    {
        ViolationCode::create(['code' => 'OLD1', 'violation_name' => 'Old code', 'first_offense' => 1, 'second_offense' => 2, 'third_offense' => 3, 'fourth_offense' => 4]);

        $this->seed(ViolationCodeSeeder::class);

        $this->assertSame(count(ViolationCodeSeeder::CODES), ViolationCode::count());
        $this->assertFalse(ViolationCode::where('code', 'OLD1')->exists());
        $this->assertSame(['UV01', 'UV02', 'UV03', 'UV04', 'UV05', 'UV06', 'UV07', 'UV08', 'UV09', 'UV10', 'UV11', 'UV12', 'UV13', 'UV14'], ViolationCode::orderBy('id')->pluck('code')->all());

        foreach (ViolationCode::all() as $code) {
            $this->assertGreaterThan(0, (float) $code->first_offense);
            $this->assertGreaterThanOrEqual((float) $code->first_offense, (float) $code->second_offense);
            $this->assertGreaterThanOrEqual((float) $code->second_offense, (float) $code->third_offense);
            $this->assertGreaterThanOrEqual((float) $code->third_offense, (float) $code->fourth_offense);
        }

        $reckless = ViolationCode::where('violation_name', 'Reckless Driving')->first();
        $this->assertEquals(2000, $reckless->first_offense);
        $this->assertEquals(10000, $reckless->second_offense);
    }

    public function test_seeder_is_repeatable(): void
    {
        $this->seed(ViolationCodeSeeder::class);
        $this->seed(ViolationCodeSeeder::class);

        $this->assertSame(count(ViolationCodeSeeder::CODES), ViolationCode::count());
    }

    // ── Auto-priced violations ───────────────────────────────────────────

    public function test_violation_log_page_hands_the_form_each_drivers_earlier_offenses(): void
    {
        $manager = $this->staff('driver_manager');
        [$user] = $this->driver();
        $a = ViolationCode::create(['code' => 'T1', 'violation_name' => 'A', 'first_offense' => 100, 'second_offense' => 200, 'third_offense' => 300, 'fourth_offense' => 400]);
        $b = ViolationCode::create(['code' => 'T2', 'violation_name' => 'B', 'first_offense' => 100, 'second_offense' => 200, 'third_offense' => 300, 'fourth_offense' => 400]);

        foreach ([1, 2] as $n) {
            ViolationLog::create([
                'user_id' => $user->id, 'vc_id' => $a->id, 'violation_instance' => (string) $n, 'violation_fine' => 100,
                'date_of_violation' => today()->toDateString(), 'time_of_violation' => '10:00:00', 'place_of_violation' => 'X',
            ]);
        }

        $response = $this->actingAs($manager)->get(route('driver-manager.violations-log'));

        $response->assertOk();
        $counts = $response->viewData('offenseCounts');
        $this->assertSame(2, $counts[$user->id][$a->id]);
        $this->assertArrayNotHasKey($b->id, $counts[$user->id]);
    }

    public function test_violation_form_selects_the_next_offense_for_the_driver(): void
    {
        $manager = $this->staff('driver_manager');

        $this->actingAs($manager)->get(route('driver-manager.violations-log'))
            ->assertOk()
            ->assertSee('autoSelectOffense', false)
            ->assertSee('nextOffense', false);
    }

    // ── Auto clock-out ───────────────────────────────────────────────────

    private function openShift(Driver $driver, string $date, string $timeIn, ?Vehicle $vehicle = null): TimeKeeping
    {
        return TimeKeeping::create([
            'driver_id' => $driver->id, 'vehicle_id' => $vehicle?->id, 'date' => $date, 'time_in' => $timeIn,
            'sick' => 0, 'vacation' => 0,
        ]);
    }

    public function test_shift_open_too_long_is_auto_closed_and_flagged(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-23 21:00:00', 'Asia/Manila'));
        [, $driver] = $this->driver();
        $vehicle = Vehicle::factory()->withDriver($driver)->create(['status' => 'active', 'is_active' => true]);
        VehicleLocation::create(['vehicle_id' => $vehicle->id, 'latitude' => 10.3, 'longitude' => 123.9, 'last_update' => now(), 'user_id' => $driver->user_id]);
        $entry = $this->openShift($driver, '2026-10-23', '06:00 AM', $vehicle); // open for 15 h

        $this->artisan('shifts:auto-close')->assertSuccessful();

        $entry->refresh();
        $this->assertNotNull($entry->time_out);
        $this->assertNotNull($entry->auto_closed_at);
        $this->assertNull($entry->reviewed_at);
        $this->assertEquals(8, $entry->hours_worked);
        $this->assertEquals(0, $entry->overtime_hours);
        $this->assertSame('inactive', $driver->fresh()->status);
        $this->assertFalse(VehicleLocation::where('vehicle_id', $vehicle->id)->exists());
        $this->assertEquals(0, $vehicle->fresh()->is_active);
    }

    public function test_recent_shift_is_left_alone(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-23 10:00:00', 'Asia/Manila'));
        [, $driver] = $this->driver();
        $entry = $this->openShift($driver, '2026-10-23', '08:00 AM');

        $this->artisan('shifts:auto-close')->assertSuccessful();

        $this->assertNull($entry->fresh()->time_out);
        $this->assertSame('active', $driver->fresh()->status);
    }

    public function test_shift_left_open_past_midnight_is_closed(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-23 00:30:00', 'Asia/Manila'));
        [, $driver] = $this->driver();
        $entry = $this->openShift($driver, '2026-10-22', '10:00 PM'); // only 2.5 h old, but a new day

        $this->artisan('shifts:auto-close')->assertSuccessful();

        $entry->refresh();
        $this->assertNotNull($entry->auto_closed_at);
        $this->assertEquals(2.5, $entry->hours_worked, 'never credits more than has elapsed');
    }

    public function test_midnight_rule_can_be_turned_off(): void
    {
        config(['timekeeping.auto_close_at_midnight' => false]);
        Carbon::setTestNow(Carbon::parse('2026-10-23 00:30:00', 'Asia/Manila'));
        [, $driver] = $this->driver();
        $entry = $this->openShift($driver, '2026-10-22', '10:00 PM');

        $this->artisan('shifts:auto-close')->assertSuccessful();

        $this->assertNull($entry->fresh()->time_out);
    }

    public function test_driver_with_a_newer_shift_stays_active(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-23 09:00:00', 'Asia/Manila'));
        [, $driver] = $this->driver();
        $old = $this->openShift($driver, '2026-10-22', '07:00 AM'); // forgotten yesterday
        $today = $this->openShift($driver, '2026-10-23', '06:00 AM'); // on the road now

        $this->artisan('shifts:auto-close')->assertSuccessful();

        $this->assertNotNull($old->fresh()->auto_closed_at);
        $this->assertNull($today->fresh()->time_out);
        $this->assertSame('active', $driver->fresh()->status);
    }

    public function test_leave_entries_and_finished_shifts_are_ignored(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-23 12:00:00', 'Asia/Manila'));
        [, $driver] = $this->driver();
        $sick = TimeKeeping::create(['driver_id' => $driver->id, 'date' => '2026-10-20', 'time_in' => null, 'time_out' => null, 'sick' => 1, 'vacation' => 0]);
        $done = TimeKeeping::create(['driver_id' => $driver->id, 'date' => '2026-10-21', 'time_in' => '06:00 AM', 'time_out' => '02:00 PM', 'hours_worked' => 8, 'overtime_hours' => 0, 'sick' => 0, 'vacation' => 0]);

        $this->artisan('shifts:auto-close')->assertSuccessful();

        $this->assertNull($sick->fresh()->auto_closed_at);
        $this->assertNull($done->fresh()->auto_closed_at);
    }

    public function test_auto_closing_is_audited(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-23 21:00:00', 'Asia/Manila'));
        [, $driver] = $this->driver();
        $entry = $this->openShift($driver, '2026-10-23', '06:00 AM');

        $this->artisan('shifts:auto-close')->assertSuccessful();

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => TimeKeeping::class,
            'subject_id' => $entry->id,
            'log_name' => 'timekeeping',
        ]);
        $this->assertTrue(\Spatie\Activitylog\Models\Activity::where('subject_id', $entry->id)->where('description', 'like', 'Auto-closed%')->exists());
    }

    public function test_driver_manager_can_review_an_auto_closed_shift(): void
    {
        $manager = $this->staff('driver_manager');
        [, $driver] = $this->driver();
        $entry = TimeKeeping::create([
            'driver_id' => $driver->id, 'date' => '2026-10-20', 'time_in' => '06:00 AM', 'time_out' => '02:00 PM',
            'hours_worked' => 8, 'overtime_hours' => 0, 'sick' => 0, 'vacation' => 0, 'auto_closed_at' => now(),
        ]);

        $page = $this->actingAs($manager)->get(route('driver-manager.time-keeping'));
        $page->assertOk();
        $this->assertSame(1, $page->viewData('needsReview'));

        $this->actingAs($manager)->patch(route('driver-manager.time-keeping.review', $entry))
            ->assertSessionHas('success');
        $this->assertNotNull($entry->fresh()->reviewed_at);

        $this->actingAs($manager)->patch(route('driver-manager.time-keeping.review', $entry))
            ->assertSessionHas('error');
    }

    public function test_review_is_refused_for_a_normal_entry_and_for_other_roles(): void
    {
        [$user, $driver] = $this->driver();
        $entry = TimeKeeping::create(['driver_id' => $driver->id, 'date' => '2026-10-20', 'time_in' => '06:00 AM', 'time_out' => '02:00 PM', 'hours_worked' => 8, 'overtime_hours' => 0, 'sick' => 0, 'vacation' => 0]);

        $this->actingAs($this->staff('driver_manager'))->patch(route('driver-manager.time-keeping.review', $entry))
            ->assertSessionHas('error');
        $this->actingAs($user)->patch(route('driver-manager.time-keeping.review', $entry))->assertForbidden();
        $this->assertNull($entry->fresh()->reviewed_at);
    }

    public function test_location_broadcast_stops_after_the_shift_is_auto_closed(): void
    {
        [$user, $driver] = $this->driver();
        $vehicle = Vehicle::factory()->withDriver($driver)->create(['status' => 'active']);
        TimeKeeping::create([
            'driver_id' => $driver->id, 'vehicle_id' => $vehicle->id, 'date' => today()->toDateString(), 'time_in' => '06:00 AM', 'time_out' => '02:00 PM',
            'hours_worked' => 8, 'overtime_hours' => 0, 'sick' => 0, 'vacation' => 0, 'auto_closed_at' => now(),
        ]);

        $this->assertTrue($driver->fresh()->shiftWasAutoClosed());

        $this->actingAs($user)->postJson(route('vehicle.broadcast'), [
            'vehicle_id' => (string) $vehicle->id, 'latitude' => 10.3, 'longitude' => 123.9, 'user_id' => $user->id,
        ])->assertForbidden()->assertJsonPath('message', 'Your shift was closed automatically. Clock in on your next shift to resume.');
    }

    public function test_a_new_shift_resumes_the_feed(): void
    {
        [, $driver] = $this->driver();
        TimeKeeping::create([
            'driver_id' => $driver->id, 'date' => today()->subDay()->toDateString(), 'time_in' => '06:00 AM', 'time_out' => '02:00 PM',
            'hours_worked' => 8, 'overtime_hours' => 0, 'sick' => 0, 'vacation' => 0, 'auto_closed_at' => now()->subHour(),
        ]);
        $this->assertTrue($driver->fresh()->shiftWasAutoClosed());

        $this->openShift($driver, today()->toDateString(), '06:00 AM');

        $this->assertFalse($driver->fresh()->shiftWasAutoClosed());
    }

    public function test_auto_close_is_scheduled(): void
    {
        $events = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())->pluck('command')->implode("\n");

        $this->assertStringContainsString('shifts:auto-close', $events);
        $this->assertStringContainsString('maintenance:due-alerts', $events);
    }

    // ── Maintenance due alerts ───────────────────────────────────────────

    private function task(?int $km, ?int $months, string $name = 'Oil change'): MaintenanceTask
    {
        return MaintenanceTask::create(['tasks_performed' => $name.' '.uniqid(), 'miles_between_service' => $km, 'months_between_service' => $months]);
    }

    private function serviced(Vehicle $vehicle, MaintenanceTask $task, string $date, int $odo): PreventiveMaintenance
    {
        return PreventiveMaintenance::create([
            'vehicle_id' => $vehicle->id, 'task_id' => $task->id, 'last_service_date' => $date,
            'last_service_odo' => $odo, 'last_service_cost' => 500,
        ]);
    }

    public function test_due_status_by_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-23 08:00:00'));
        $vehicle = Vehicle::factory()->create(['status' => 'active']);
        $task = $this->task(null, 3);

        $overdue = $this->serviced($vehicle, $task, '2026-07-01', 1000)->load('maintenanceTask'); // due Oct 1
        $this->assertSame('overdue', $overdue->dueStatus());

        $overdue->update(['last_service_date' => '2026-07-28']); // due Oct 28 → in 5 days
        $this->assertSame('due_soon', $overdue->fresh('maintenanceTask')->dueStatus());

        $overdue->update(['last_service_date' => '2026-09-01']); // due Dec 1
        $this->assertSame('ok', $overdue->fresh('maintenanceTask')->dueStatus());
    }

    public function test_due_status_by_distance(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-23 08:00:00'));
        $vehicle = Vehicle::factory()->create(['status' => 'active']);
        $task = $this->task(3000, null);
        $pm = $this->serviced($vehicle, $task, '2026-10-01', 40000)->load('maintenanceTask'); // next due at 43,000

        $this->assertSame('ok', $pm->dueStatus(41000));
        $this->assertSame('due_soon', $pm->dueStatus(42600), '400 km to go');
        $this->assertSame('overdue', $pm->dueStatus(43000));
        $this->assertSame('ok', $pm->dueStatus(null), 'no odometer reading, no distance alert');
    }

    public function test_a_task_with_no_interval_is_never_due(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-23 08:00:00'));
        $vehicle = Vehicle::factory()->create(['status' => 'active']);
        $pm = $this->serviced($vehicle, $this->task(null, null, 'Bulb'), '2020-01-01', 1000)->load('maintenanceTask');

        $this->assertSame('ok', $pm->dueStatus(999999));
    }

    public function test_a_later_service_shows_an_earlier_one_is_coming_up(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-23 08:00:00'));
        $vehicle = Vehicle::factory()->create(['status' => 'active']);
        $this->serviced($vehicle, $this->task(3000, null, 'Oil'), '2026-08-01', 40000);       // due at 43,000
        $this->serviced($vehicle, $this->task(60000, null, 'Hose'), '2026-10-20', 42700);     // current reading 42,700

        $row = app(\App\Services\MaintenanceDueService::class)->forVehicle($vehicle);

        $this->assertCount(1, $row['due_soon']);
        $this->assertStringStartsWith('Oil', $row['due_soon']->first()['task']);
        $this->assertSame(300, $row['due_soon']->first()['km_left']);
        $this->assertCount(0, $row['overdue']);
    }

    public function test_disposed_vehicles_are_not_alerted(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-23 08:00:00'));
        $vehicle = Vehicle::factory()->create(['status' => 'disposed']);
        $this->serviced($vehicle, $this->task(null, 1), '2025-01-01', 1000);

        $this->assertFalse(app(\App\Services\MaintenanceDueService::class)->fleet()->has($vehicle->id));
    }

    public function test_due_alert_command_emails_the_maintenance_managers(): void
    {
        Notification::fake();
        PreventiveMaintenance::query()->delete(); // the dev database may already hold services
        Carbon::setTestNow(Carbon::parse('2026-10-23 08:00:00'));
        $manager = $this->staff('maintenance_manager');
        $other = $this->staff('driver_manager');
        $vehicle = Vehicle::factory()->create(['status' => 'active']);
        $this->serviced($vehicle, $this->task(null, 3), '2026-07-01', 1000);

        $this->artisan('maintenance:due-alerts')->assertSuccessful();

        Notification::assertSentTo($manager, MaintenanceDueDigest::class, function ($n) use ($manager) {
            $mail = $n->toMail($manager);
            return str_contains($mail->subject, '1 overdue');
        });
        Notification::assertNotSentTo($other, MaintenanceDueDigest::class);
    }

    public function test_due_alert_command_stays_quiet_when_nothing_is_due(): void
    {
        Notification::fake();
        $this->staff('maintenance_manager');
        PreventiveMaintenance::query()->delete();

        $this->artisan('maintenance:due-alerts')->expectsOutput('Nothing is overdue or due soon.')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_dry_run_sends_nothing(): void
    {
        Notification::fake();
        Carbon::setTestNow(Carbon::parse('2026-10-23 08:00:00'));
        $this->staff('maintenance_manager');
        $vehicle = Vehicle::factory()->create(['status' => 'active']);
        $this->serviced($vehicle, $this->task(null, 3), '2026-07-01', 1000);

        $this->artisan('maintenance:due-alerts --dry-run')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_vehicles_page_and_dashboard_flag_overdue_vehicles(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-23 08:00:00'));
        $manager = $this->staff('maintenance_manager');
        $vehicle = Vehicle::factory()->create(['status' => 'active']);
        $task = $this->task(null, 3, 'Oil');
        $this->serviced($vehicle, $task, '2026-07-01', 1000);

        $page = $this->actingAs($manager)->get(route('vehicles.index'));
        $page->assertOk();
        $row = collect($page->viewData('vehicles'))->firstWhere('id', $vehicle->id);
        $this->assertCount(1, $row['maintenance_overdue']);

        $dash = $this->actingAs($manager)->get(route('dashboard'));
        $dash->assertOk()->assertSee('Maintenance needs attention')->assertSee($vehicle->plate_number);
    }

    public function test_preventive_page_no_longer_marks_distance_only_tasks_overdue_straight_away(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-23 08:00:00'));
        $manager = $this->staff('maintenance_manager');
        $vehicle = Vehicle::factory()->create(['status' => 'active']);
        $task = $this->task(15000, null, 'Brake pads'); // no time interval
        $this->serviced($vehicle, $task, '2026-01-01', 20000);

        $page = $this->actingAs($manager)->get(route('maintenance-manager.preventive-maintenance', ['vehicle_id' => $vehicle->id]));

        $page->assertOk();
        $this->assertSame(20000, (int) $page->viewData('currentOdo'));
        $page->assertDontSee('Past');
    }

    public function test_overdue_vehicle_can_go_active_unless_the_block_is_on(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-23 08:00:00'));
        $manager = $this->staff('maintenance_manager');
        $vehicle = Vehicle::factory()->create(['status' => 'maintenance', 'acquisition_date' => '2020-01-01', 'exp_disposal_date' => null, 'year' => 2020]);
        $this->serviced($vehicle, $this->task(null, 3, 'Oil'), '2026-07-01', 1000);

        $payload = [
            'year' => 2020, 'brand' => $vehicle->brand, 'model' => $vehicle->model, 'plate_number' => $vehicle->plate_number,
            'status' => 'active', 'acquisition_date' => '2020-01-01',
        ];

        config(['maintenance.block_active_when_overdue' => true]);
        $this->actingAs($manager)->patch(route('vehicles.update', $vehicle), $payload)->assertSessionHasErrors('status');
        $this->assertSame('maintenance', $vehicle->fresh()->status);

        config(['maintenance.block_active_when_overdue' => false]);
        $this->actingAs($manager)->patch(route('vehicles.update', $vehicle), $payload)->assertSessionHasNoErrors();
        $this->assertSame('active', $vehicle->fresh()->status);
    }

    // ── Driver inactivity: warn, then suspend ────────────────────────────

    /** A driver whose last clock-in was $days days before 2026-10-26 (the frozen "today"). */
    private function inactiveDriver(int $days, array $overrides = [], ?int $warnedDaysAgo = null): array
    {
        Carbon::setTestNow(Carbon::parse('2026-10-26 08:00:00'));
        [$user, $driver] = $this->driver('inactive');
        $warned = $warnedDaysAgo === null ? [] : ['inactivity_warned_at' => now()->subDays($warnedDaysAgo)];
        $driver->forceFill(['created_at' => today()->subDays(60)] + $warned + $overrides)->save();
        TimeKeeping::create([
            'driver_id' => $driver->id, 'date' => today()->subDays($days)->toDateString(),
            'time_in' => '08:00 AM', 'time_out' => '05:00 PM', 'hours_worked' => 8, 'overtime_hours' => 0, 'sick' => 0, 'vacation' => 0,
        ]);

        return [$user, $driver->fresh()];
    }

    public function test_driver_inactive_for_three_days_is_emailed_to_contact_hr(): void
    {
        Notification::fake();
        config(['timekeeping.hr_email' => 'hr@example.com']);
        [$user, $driver] = $this->inactiveDriver(3);

        $this->artisan('drivers:inactivity-check')->assertSuccessful();

        Notification::assertSentTo($user, DriverInactivityWarning::class, function ($n) use ($user) {
            $mail = $n->toMail($user);

            return str_contains(implode(' ', $mail->introLines), 'hr@example.com');
        });
        $this->assertNotNull($driver->fresh()->inactivity_warned_at);
        $this->assertFalse($driver->fresh()->isSuspended());
    }

    public function test_driver_inactive_for_two_days_is_left_alone(): void
    {
        Notification::fake();
        [$user, $driver] = $this->inactiveDriver(2);

        $this->artisan('drivers:inactivity-check')->assertSuccessful();

        Notification::assertNothingSentTo($user);
        $this->assertNull($driver->fresh()->inactivity_warned_at);
    }

    public function test_driver_is_warned_only_once(): void
    {
        Notification::fake();
        [$user] = $this->inactiveDriver(4);

        $this->artisan('drivers:inactivity-check');
        $this->artisan('drivers:inactivity-check');

        Notification::assertSentToTimes($user, DriverInactivityWarning::class, 1);
    }

    public function test_driver_inactive_over_a_week_and_already_warned_is_suspended(): void
    {
        Notification::fake();
        [, $driver] = $this->inactiveDriver(8, [], 5);

        $this->artisan('drivers:inactivity-check')->assertSuccessful();

        $driver->refresh();
        $this->assertTrue($driver->isSuspended());
        $this->assertSame('suspended', $driver->status);
        $this->assertStringContainsString('inactive for 8 days', $driver->suspension_reason);
        $this->assertTrue(Activity::where('log_name', 'driver')->where('event', 'suspended')->where('subject_id', $driver->id)->exists());
    }

    public function test_exactly_a_week_of_inactivity_is_not_yet_suspended(): void
    {
        Notification::fake();
        [, $driver] = $this->inactiveDriver(7, [], 4);

        $this->artisan('drivers:inactivity-check');

        $this->assertFalse($driver->fresh()->isSuspended());
    }

    public function test_driver_never_warned_is_warned_first_not_suspended(): void
    {
        Notification::fake();
        [$user, $driver] = $this->inactiveDriver(20);

        $this->artisan('drivers:inactivity-check');

        Notification::assertSentTo($user, DriverInactivityWarning::class);
        $this->assertFalse($driver->fresh()->isSuspended());
    }

    public function test_a_warning_sent_today_is_given_a_day_before_suspension(): void
    {
        Notification::fake();
        [, $driver] = $this->inactiveDriver(20, [], 0);

        $this->artisan('drivers:inactivity-check');

        $this->assertFalse($driver->fresh()->isSuspended());
    }

    public function test_clocking_in_again_clears_the_warning(): void
    {
        Notification::fake();
        [, $driver] = $this->inactiveDriver(1, [], 3);

        $this->artisan('drivers:inactivity-check');

        $this->assertNull($driver->fresh()->inactivity_warned_at);
    }

    public function test_recorded_leave_counts_as_activity(): void
    {
        Notification::fake();
        [$user, $driver] = $this->inactiveDriver(10);
        TimeKeeping::create(['driver_id' => $driver->id, 'date' => today()->subDay()->toDateString(), 'hours_worked' => 0, 'overtime_hours' => 0, 'sick' => 1, 'vacation' => 0]);

        $this->artisan('drivers:inactivity-check');

        Notification::assertNothingSentTo($user);
    }

    public function test_a_never_clocked_in_driver_counts_from_when_the_account_was_created(): void
    {
        Notification::fake();
        Carbon::setTestNow(Carbon::parse('2026-10-26 08:00:00'));
        [$user, $driver] = $this->driver('inactive');
        $driver->forceFill(['created_at' => today()->subDays(3)])->save();

        $this->artisan('drivers:inactivity-check');

        Notification::assertSentTo($user, DriverInactivityWarning::class);
    }

    public function test_suspended_pending_and_rejected_drivers_are_skipped(): void
    {
        Notification::fake();
        [$a] = $this->inactiveDriver(10, ['is_suspended' => true]);
        [$b] = $this->inactiveDriver(10, ['is_approved' => 0]);
        [$c] = $this->inactiveDriver(10, ['is_rejected' => 1, 'is_approved' => 0]);

        $this->artisan('drivers:inactivity-check');

        Notification::assertNothingSentTo($a);
        Notification::assertNothingSentTo($b);
        Notification::assertNothingSentTo($c);
    }

    public function test_dry_run_changes_nothing(): void
    {
        Notification::fake();
        [$user, $driver] = $this->inactiveDriver(10, [], 5);

        $this->artisan('drivers:inactivity-check --dry-run')->assertSuccessful();

        Notification::assertNothingSentTo($user);
        $this->assertFalse($driver->fresh()->isSuspended());
    }

    public function test_a_reinstated_driver_gets_a_fresh_inactivity_clock(): void
    {
        Notification::fake();
        [$user, $driver] = $this->inactiveDriver(20, [], 10);
        $driver->suspend('Inactive');
        $driver->unsuspend();

        $this->artisan('drivers:inactivity-check');

        $driver->refresh();
        $this->assertFalse($driver->isSuspended());
        $this->assertNull($driver->inactivity_warned_at);
        Notification::assertNothingSentTo($user);
    }

    public function test_the_check_is_scheduled_daily(): void
    {
        $events = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
            ->filter(fn ($e) => str_contains($e->command, 'drivers:inactivity-check'));

        $this->assertCount(1, $events);
        $this->assertSame('0 8 * * *', $events->first()->expression);
    }
}
