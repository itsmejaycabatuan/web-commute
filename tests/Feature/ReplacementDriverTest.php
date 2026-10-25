<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Driver;
use App\Models\DriverVacancy;
use App\Models\TimeKeeping;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDutyDay;
use App\Models\ViolationCode;
use App\Models\ViolationLog;
use App\Notifications\LicenceExpiredRelease;
use App\Notifications\ReplacementAssigned;
use App\Notifications\VehicleVacancyFilled;
use App\Notifications\VehicleVacancyOpen;
use App\Services\ReplacementDriverService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * A vehicle that loses its driver (suspension, expired licence) gets the best free driver by itself.
 */
class ReplacementDriverTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'commuter', 'driver', 'driver_manager', 'maintenance_manager'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $this->withoutMiddleware(VerifyCsrfToken::class);

        // The development database holds drivers of its own; none of them may be picked by these tests.
        Driver::query()->update(['is_approved' => false]);
        config(['fleet.auto_replace' => true, 'fleet.release_expired_licences' => true, 'fleet.roster_days' => 10]);
    }

    /** A driver who could take a vehicle: approved, licence valid, no vehicle. */
    private function free(array $state = []): Driver
    {
        $user = User::factory()->create(['email_verified_at' => now()])->assignRole('driver');

        return Driver::factory()->approved()->create(array_merge([
            'user_id' => $user->id, 'status' => 'inactive', 'is_suspended' => false,
            'expiration_date' => now()->addYear()->toDateString(), 'inactivity_warned_at' => null,
        ], $state));
    }

    private function vehicleWith(Driver ...$drivers): Vehicle
    {
        return Vehicle::factory()->withDriver(...$drivers)->create(['status' => 'active']);
    }

    private function manager(): User
    {
        return User::factory()->create(['email_verified_at' => now()])->assignRole('maintenance_manager');
    }

    private function driversOf(Vehicle $vehicle): array
    {
        return $vehicle->drivers()->pluck('drivers.id')->sort()->values()->all();
    }

    // ── Suspension ───────────────────────────────────────────────────────

    public function test_suspending_a_vehicles_only_driver_assigns_the_best_free_driver(): void
    {
        Notification::fake();
        $manager = $this->manager();
        $leaving = $this->free();
        $vehicle = $this->vehicleWith($leaving);
        $replacement = $this->free();

        $leaving->suspend('Test');

        $this->assertSame([$replacement->id], $this->driversOf($vehicle));
        $vacancy = DriverVacancy::where('vehicle_id', $vehicle->id)->firstOrFail();
        $this->assertSame('filled', $vacancy->resolution);
        $this->assertSame($replacement->id, $vacancy->replacement_driver_id);
        $this->assertSame($leaving->id, $vacancy->former_driver_id);
        $this->assertSame('suspended', $vacancy->reason);
        $this->assertNotNull($vacancy->resolved_at);

        Notification::assertSentTo($replacement->user, ReplacementAssigned::class, fn ($n) => $n->vehicle->id === $vehicle->id && $n->coDriver === null);
        Notification::assertSentTo($manager, VehicleVacancyFilled::class);
        Notification::assertNotSentTo($manager, VehicleVacancyOpen::class);
        $this->assertTrue(Activity::where('event', 'replacement_assigned')->where('subject_id', $vehicle->id)->exists());
        $this->assertTrue(Activity::where('event', 'vacancy_opened')->where('subject_id', $vehicle->id)->exists());
    }

    public function test_on_a_shared_vehicle_the_replacement_takes_the_seat_and_the_roster_is_rebuilt(): void
    {
        Notification::fake();
        $stays = $this->free();
        $leaving = $this->free();
        $vehicle = $this->vehicleWith($stays, $leaving);
        foreach (range(0, 5) as $i) {
            VehicleDutyDay::create(['vehicle_id' => $vehicle->id, 'driver_id' => $i % 2 ? $leaving->id : $stays->id, 'duty_date' => today()->addDays($i)->toDateString()]);
        }
        $replacement = $this->free();

        $leaving->suspend();

        $this->assertSame(collect([$stays->id, $replacement->id])->sort()->values()->all(), $this->driversOf($vehicle));

        $days = VehicleDutyDay::where('vehicle_id', $vehicle->id)->whereDate('duty_date', '>=', today())->orderBy('duty_date')->get();
        $this->assertCount(10, $days, 'the configured number of days');
        $this->assertSame([$stays->id, $replacement->id, $stays->id, $replacement->id], $days->take(4)->pluck('driver_id')->all(), 'the driver already on the vehicle first, then taking turns');
        $this->assertNotContains($leaving->id, $days->pluck('driver_id')->all());

        $this->assertSame($stays->id, $vehicle->fresh()->dutyDriverOn(today())->id);
        $this->assertSame($replacement->id, $vehicle->fresh()->dutyDriverOn(today()->addDay())->id);

        Notification::assertSentTo($replacement->user, ReplacementAssigned::class, fn ($n) => $n->coDriver?->id === $stays->id);
    }

    public function test_the_replacement_email_names_the_co_driver_and_the_next_duty_day(): void
    {
        $stays = $this->free(['name' => 'Ramon Dela Cruz']);
        $leaving = $this->free();
        $vehicle = $this->vehicleWith($stays, $leaving);
        $replacement = $this->free();

        $leaving->suspend();

        $vacancy = DriverVacancy::where('vehicle_id', $vehicle->id)->firstOrFail();
        $mail = (new ReplacementAssigned($vehicle, $vacancy, $stays))->toMail($replacement->user->fresh());
        $text = implode("\n", $mail->introLines);

        $this->assertStringContainsString($vehicle->plate_number, $mail->subject);
        $this->assertStringContainsString('Ramon Dela Cruz', $text);
        $this->assertStringContainsString(today()->addDay()->format('l, M j'), $text);
    }

    public function test_both_drivers_of_a_shared_vehicle_can_be_replaced(): void
    {
        $a = $this->free();
        $b = $this->free();
        $vehicle = $this->vehicleWith($a, $b);
        $first = $this->free();
        $second = $this->free();

        $a->suspend();
        $b->suspend();

        $this->assertSame(collect([$first->id, $second->id])->sort()->values()->all(), $this->driversOf($vehicle));
        $this->assertSame(0, DriverVacancy::open()->where('vehicle_id', $vehicle->id)->count());
        $this->assertSame(2, DriverVacancy::where('vehicle_id', $vehicle->id)->where('resolution', 'filled')->count());
    }

    public function test_an_admin_suspending_a_driver_triggers_it_too(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()])->assignRole('admin');
        $leaving = $this->free();
        $vehicle = $this->vehicleWith($leaving);
        $replacement = $this->free();

        $this->actingAs($admin)->patch(route('drivers.suspension', $leaving), ['suspension_reason' => 'Test'])->assertSessionHas('success');

        $this->assertTrue($leaving->fresh()->is_suspended);
        $this->assertSame([$replacement->id], $this->driversOf($vehicle));
    }

    public function test_a_failure_finding_a_replacement_never_undoes_the_suspension(): void
    {
        $leaving = $this->free();
        $this->vehicleWith($leaving);
        $this->app->bind(ReplacementDriverService::class, fn () => throw new \RuntimeException('boom'));

        $leaving->suspend('Test');

        $this->assertTrue($leaving->fresh()->is_suspended);
        $this->assertSame(0, $leaving->fresh()->vehicle()->count());
    }

    // ── Who is picked ────────────────────────────────────────────────────

    public function test_candidates_are_ranked_by_licence_violations_then_overtime(): void
    {
        $code = ViolationCode::create(['code' => 'T'.uniqid(), 'violation_name' => 'T', 'first_offense' => 1, 'second_offense' => 1, 'third_offense' => 1, 'fourth_offense' => 1]);
        $clean = $this->free();
        $tired = $this->free();
        $violator = $this->free();
        $expiring = $this->free(['expiration_date' => now()->addDays(10)->toDateString()]);

        TimeKeeping::create(['driver_id' => $tired->id, 'date' => today()->subDays(3)->toDateString(), 'time_in' => '06:00 AM', 'time_out' => '08:00 PM', 'hours_worked' => 14, 'overtime_hours' => 6, 'sick' => 0, 'vacation' => 0]);
        ViolationLog::create(['user_id' => $violator->user_id, 'vc_id' => $code->id, 'violation_instance' => '1', 'violation_fine' => 1, 'date_of_violation' => today()->subDays(5)->toDateString(), 'time_of_violation' => '10:00:00', 'place_of_violation' => 'X']);
        ViolationLog::create(['user_id' => $expiring->user_id, 'vc_id' => $code->id, 'violation_instance' => '1', 'violation_fine' => 1, 'date_of_violation' => today()->subYear()->toDateString(), 'time_of_violation' => '10:00:00', 'place_of_violation' => 'X']);

        $ranked = app(ReplacementDriverService::class)->candidates()->pluck('id')->all();

        $this->assertSame([$clean->id, $tired->id, $violator->id, $expiring->id], $ranked);
    }

    public function test_candidates_exclude_everyone_who_could_not_drive(): void
    {
        $this->free(['is_suspended' => true]);
        $this->free(['is_approved' => false]);
        $this->free(['is_rejected' => true]);
        $this->free(['expiration_date' => now()->subDay()->toDateString()]);
        $this->free(['expiration_date' => null]);
        $this->free(['inactivity_warned_at' => now()->subDay()]);
        $this->vehicleWith($busy = $this->free());
        $ok = $this->free();

        $ids = app(ReplacementDriverService::class)->candidates()->pluck('id')->all();

        $this->assertSame([$ok->id], $ids);
        $this->assertNotContains($busy->id, $ids);
    }

    // ── Nobody is free ───────────────────────────────────────────────────

    public function test_with_nobody_free_the_vacancy_stays_open_and_the_managers_are_told(): void
    {
        Notification::fake();
        $manager = $this->manager();
        $leaving = $this->free();
        $vehicle = $this->vehicleWith($leaving);

        $leaving->suspend();

        $vacancy = DriverVacancy::where('vehicle_id', $vehicle->id)->firstOrFail();
        $this->assertNull($vacancy->resolved_at);
        $this->assertNotNull($vacancy->last_notified_at);
        $this->assertSame([], $this->driversOf($vehicle));
        Notification::assertSentTo($manager, VehicleVacancyOpen::class, fn ($n) => $n->vacancies->first()->vehicle_id === $vehicle->id && $n->suggested->isEmpty());
        Notification::assertNotSentTo($manager, VehicleVacancyFilled::class);
    }

    public function test_the_hourly_job_fills_an_open_vacancy_once_a_driver_is_free(): void
    {
        Notification::fake();
        $manager = $this->manager();
        $leaving = $this->free();
        $vehicle = $this->vehicleWith($leaving);
        $leaving->suspend();
        $this->assertSame([], $this->driversOf($vehicle));

        $newcomer = $this->free(); // approved after the suspension

        $this->artisan('vehicles:cover-vacancies')->assertSuccessful();

        $this->assertSame([$newcomer->id], $this->driversOf($vehicle));
        $this->assertSame(0, DriverVacancy::open()->where('vehicle_id', $vehicle->id)->count());
        Notification::assertSentTo($manager, VehicleVacancyFilled::class);
        Notification::assertSentTo($newcomer->user, ReplacementAssigned::class);
    }

    public function test_an_unfilled_vacancy_is_reported_again_each_day_but_not_every_hour(): void
    {
        Notification::fake();
        $manager = $this->manager();
        $leaving = $this->free();
        $vehicle = $this->vehicleWith($leaving);
        $leaving->suspend();

        $this->artisan('vehicles:cover-vacancies')->assertSuccessful();
        Notification::assertSentToTimes($manager, VehicleVacancyOpen::class, 1); // only the first, at suspension

        DriverVacancy::where('vehicle_id', $vehicle->id)->update(['last_notified_at' => now()->subHours(25)]);
        $this->artisan('vehicles:cover-vacancies')->assertSuccessful();
        Notification::assertSentToTimes($manager, VehicleVacancyOpen::class, 2);
    }

    public function test_with_auto_replace_off_the_vacancy_is_left_open_with_suggestions(): void
    {
        Notification::fake();
        config(['fleet.auto_replace' => false]);
        $manager = $this->manager();
        $leaving = $this->free();
        $vehicle = $this->vehicleWith($leaving);
        $candidate = $this->free();

        $leaving->suspend();
        $this->artisan('vehicles:cover-vacancies')->assertSuccessful();

        $this->assertSame([], $this->driversOf($vehicle));
        $this->assertNull(DriverVacancy::where('vehicle_id', $vehicle->id)->first()->resolved_at);
        Notification::assertSentTo($manager, VehicleVacancyOpen::class, fn ($n) => $n->suggested->pluck('id')->contains($candidate->id));

        $mail = (new VehicleVacancyOpen(DriverVacancy::open()->where('vehicle_id', $vehicle->id)->with(['vehicle', 'formerDriver'])->get(), collect([$candidate])))->toMail($manager);
        $this->assertStringContainsString('switched off', implode("\n", $mail->introLines));
    }

    // ── A vacancy that is no longer needed ───────────────────────────────

    public function test_a_vacancy_the_manager_already_filled_is_closed_not_doubled(): void
    {
        $leaving = $this->free();
        $vehicle = $this->vehicleWith($leaving);
        $leaving->suspend();                       // nobody free: stays open
        $manual = $this->free();
        $vehicle->drivers()->attach($manual->id);  // the Maintenance Manager assigned someone by hand
        $this->free();                             // and another driver is now free

        $this->artisan('vehicles:cover-vacancies')->assertSuccessful();

        $this->assertSame([$manual->id], $this->driversOf($vehicle), 'no second driver was added');
        $this->assertSame('not_needed', DriverVacancy::where('vehicle_id', $vehicle->id)->first()->resolution);
    }

    public function test_a_vehicle_that_left_service_has_no_seat_to_fill(): void
    {
        $leaving = $this->free();
        $vehicle = $this->vehicleWith($leaving);
        $leaving->suspend();
        $vehicle->update(['status' => 'maintenance']);
        $this->free();

        $this->artisan('vehicles:cover-vacancies')->assertSuccessful();

        $this->assertSame([], $this->driversOf($vehicle));
        $this->assertSame('not_needed', DriverVacancy::where('vehicle_id', $vehicle->id)->first()->resolution);
    }

    public function test_a_vehicle_that_is_not_in_service_opens_no_vacancy(): void
    {
        $leaving = $this->free();
        $vehicle = $this->vehicleWith($leaving);
        $vehicle->update(['status' => 'inactive']);

        $leaving->suspend();

        $this->assertSame(0, DriverVacancy::where('vehicle_id', $vehicle->id)->count());
    }

    // ── Expired licences ─────────────────────────────────────────────────

    public function test_a_driver_with_an_expired_licence_is_taken_off_the_vehicle_and_replaced(): void
    {
        Notification::fake();
        $manager = $this->manager();
        $expired = $this->free(['expiration_date' => now()->subDays(3)->toDateString()]);
        $vehicle = $this->vehicleWith($expired);
        $replacement = $this->free();

        $this->artisan('vehicles:cover-vacancies')->assertSuccessful();

        $this->assertSame([$replacement->id], $this->driversOf($vehicle));
        $this->assertFalse($expired->fresh()->is_suspended, 'they keep their account');
        $this->assertSame('licence_expired', DriverVacancy::where('vehicle_id', $vehicle->id)->first()->reason);
        Notification::assertSentTo($expired->user, LicenceExpiredRelease::class);
        Notification::assertSentTo($replacement->user, ReplacementAssigned::class);
        Notification::assertSentTo($manager, VehicleVacancyFilled::class);
        $this->assertTrue(Activity::where('event', 'released_from_vehicle')->where('subject_id', $expired->id)->exists());
    }

    public function test_a_licence_that_is_only_expiring_is_left_alone(): void
    {
        $expiring = $this->free(['expiration_date' => now()->addDays(5)->toDateString()]);
        $vehicle = $this->vehicleWith($expiring);
        $this->free();

        $this->artisan('vehicles:cover-vacancies')->assertSuccessful();

        $this->assertSame([$expiring->id], $this->driversOf($vehicle));
    }

    public function test_releasing_expired_licences_can_be_switched_off(): void
    {
        config(['fleet.release_expired_licences' => false]);
        $expired = $this->free(['expiration_date' => now()->subDay()->toDateString()]);
        $vehicle = $this->vehicleWith($expired);
        $this->free();

        $this->artisan('vehicles:cover-vacancies')->assertSuccessful();

        $this->assertSame([$expired->id], $this->driversOf($vehicle));
    }

    // ── Reporting ────────────────────────────────────────────────────────

    public function test_dry_run_changes_and_sends_nothing(): void
    {
        Notification::fake();
        $expired = $this->free(['expiration_date' => now()->subDay()->toDateString()]);
        $vehicle = $this->vehicleWith($expired);
        $this->free();

        $this->artisan('vehicles:cover-vacancies --dry-run')->assertSuccessful();

        $this->assertSame([$expired->id], $this->driversOf($vehicle));
        $this->assertSame(0, DriverVacancy::where('vehicle_id', $vehicle->id)->count());
        Notification::assertNothingSent();
    }

    public function test_the_maintenance_dashboard_lists_vehicles_waiting_for_a_driver(): void
    {
        $manager = $this->manager();
        $leaving = $this->free(['name' => 'Juan Leaving']);
        $vehicle = $this->vehicleWith($leaving);
        $leaving->suspend();

        $this->actingAs($manager)->get(route('dashboard'))
            ->assertOk()->assertSee('Vehicles waiting for a driver')->assertSee($vehicle->plate_number)->assertSee('Juan Leaving');
    }

    public function test_the_manager_and_licence_emails_read_well(): void
    {
        $manager = $this->manager();
        $expired = $this->free(['name' => 'Juan Expired', 'expiration_date' => now()->subDays(2)->toDateString()]);
        $vehicle = $this->vehicleWith($expired);
        $replacement = $this->free(['name' => 'Pedro Cover']);

        $this->artisan('vehicles:cover-vacancies')->assertSuccessful();

        $vacancy = DriverVacancy::where('vehicle_id', $vehicle->id)->with(['vehicle', 'formerDriver', 'replacementDriver'])->firstOrFail();

        $filled = (new VehicleVacancyFilled($vacancy))->toMail($manager);
        $this->assertStringContainsString('Pedro Cover', $filled->subject);
        $this->assertStringContainsString('Juan Expired', implode("\n", $filled->introLines));
        $this->assertStringContainsString('licence expired', implode("\n", $filled->introLines));

        $released = (new LicenceExpiredRelease($expired->fresh(), collect([$vehicle])))->toMail($expired->user);
        $this->assertStringContainsString($vehicle->plate_number, $released->subject);
        $this->assertStringContainsString('renewed', implode("\n", $released->introLines));
    }

    public function test_the_job_is_scheduled_hourly(): void
    {
        $event = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())->first(fn ($e) => str_contains($e->command, 'vehicles:cover-vacancies'));

        $this->assertNotNull($event);
        $this->assertSame('0 * * * *', $event->expression);
    }
}
