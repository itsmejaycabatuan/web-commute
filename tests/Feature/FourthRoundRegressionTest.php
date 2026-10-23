<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Driver;
use App\Models\MaintenanceTask;
use App\Models\PreventiveMaintenance;
use App\Models\TimeKeeping;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleMaintenanceLog;
use App\Models\ViolationCode;
use App\Models\ViolationLog;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Fixes from the fourth manual-testing round: vehicle-gated shifts, the "My Vehicle" button,
 * the maintenance log, dev tools, the driver record modal, violation payments, the shared
 * toast, and the always-present navigation sidebar.
 */
class FourthRoundRegressionTest extends TestCase
{
    use DatabaseTransactions;

    private ?ViolationCode $code = null;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'commuter', 'driver', 'driver_manager', 'maintenance_manager'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    protected function userWithRole(string $role): User
    {
        return User::factory()->create(['email_verified_at' => now()])->assignRole($role);
    }

    /** @return array{0: User, 1: Driver} */
    protected function driver(bool $withVehicle, string $status = 'inactive'): array
    {
        $user = $this->userWithRole('driver');
        $driver = Driver::factory()->approved()->create(['user_id' => $user->id, 'status' => $status]);

        if ($withVehicle) {
            Vehicle::factory()->withDriver($driver->id)->create(['status' => 'active']);
        }

        return [$user->fresh(), $driver];
    }

    protected function commuter(): User
    {
        $user = $this->userWithRole('commuter');
        Wallet::factory()->create(['user_id' => $user->id, 'balance' => '500']);

        return $user->fresh();
    }

    // ───────────── 1. no vehicle, no shift ─────────────

    public function test_a_driver_without_a_vehicle_cannot_clock_in()
    {
        [$user, $driver] = $this->driver(false);

        $this->actingAs($user)->post(route('driver.timekeeping.clock-in'))
            ->assertSessionHas('error');

        $this->assertSame(0, TimeKeeping::where('driver_id', $driver->id)->count());
        $this->assertSame('inactive', $driver->fresh()->status);
    }

    public function test_a_driver_without_a_vehicle_cannot_set_their_status_to_active()
    {
        [$user, $driver] = $this->driver(false);

        $this->actingAs($user)->post(route('driver.status.update'), ['status' => 'active'])
            ->assertSessionHas('error');

        $this->assertSame('inactive', $driver->fresh()->status);
    }

    public function test_a_driver_without_a_vehicle_can_still_go_inactive()
    {
        [$user, $driver] = $this->driver(false, 'active');

        $this->actingAs($user)->post(route('driver.status.update'), ['status' => 'inactive'])
            ->assertSessionHas('success');

        $this->assertSame('inactive', $driver->fresh()->status);
    }

    public function test_a_driver_with_a_vehicle_can_clock_in_and_go_active()
    {
        [$user, $driver] = $this->driver(true);

        $this->actingAs($user)->post(route('driver.timekeeping.clock-in'))->assertSessionHas('success');
        $this->assertSame(1, TimeKeeping::where('driver_id', $driver->id)->count());
        $this->assertSame('active', $driver->fresh()->status);

        $driver->update(['status' => 'inactive']);
        $this->actingAs($user)->post(route('driver.status.update'), ['status' => 'active'])->assertSessionHas('success');
        $this->assertSame('active', $driver->fresh()->status);
    }

    public function test_the_map_tells_a_driver_without_a_vehicle_why_clock_in_is_unavailable()
    {
        [$without] = $this->driver(false);
        [$with] = $this->driver(true);

        $html = $this->actingAs($without)->get(route('map'))->assertOk()->getContent();
        $this->assertStringContainsString('id="no-vehicle-notice"', $html);
        $this->assertMatchesRegularExpression('/<button type="submit"\s+disabled/', $html, 'the Clock In button is disabled');

        $html = $this->actingAs($with)->get(route('map'))->assertOk()->getContent();
        $this->assertStringNotContainsString('id="no-vehicle-notice"', $html);
    }

    // ───────────── 2. My Vehicle button ─────────────

    public function test_the_driver_dashboard_has_no_my_vehicle_button()
    {
        [$user] = $this->driver(true);

        $html = $this->actingAs($user)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringNotContainsString('My Vehicle', $html);
        $this->assertStringContainsString('Assigned', $html, 'the read-only vehicle card stays');
    }

    // ───────────── 3. maintenance log ─────────────

    public function test_every_logged_service_is_kept_in_the_maintenance_log()
    {
        $manager = $this->userWithRole('maintenance_manager');
        $vehicle = Vehicle::factory()->create(['status' => 'active']);
        $task = MaintenanceTask::create(['tasks_performed' => 'Oil Change', 'miles_between_service' => 5000, 'months_between_service' => 6]);

        foreach ([[1000, '2026-03-01', 1200], [6200, '2026-09-01', 1350]] as [$odo, $date, $cost]) {
            $this->actingAs($manager)->post(route('maintenance-manager.preventive-maintenance.store'), [
                'vehicle_id' => $vehicle->id, 'task_id' => $task->id, 'last_service_odo' => $odo,
                'last_service_date' => $date, 'last_service_cost' => $cost,
                'performed_by' => 'Cebu Auto Care', 'invoice_number' => 'INV-'.$odo, 'comments' => 'Serviced',
            ])->assertSessionHas('success');
        }

        // The schedule only remembers the latest service...
        $this->assertSame(1, PreventiveMaintenance::where('vehicle_id', $vehicle->id)->count());
        $this->assertSame(6200, PreventiveMaintenance::where('vehicle_id', $vehicle->id)->value('last_service_odo'));

        // ...the log remembers both.
        $logs = VehicleMaintenanceLog::where('vehicle_id', $vehicle->id)->orderBy('service_date')->get();
        $this->assertCount(2, $logs);
        $this->assertSame([1000, 6200], $logs->pluck('mileage_at_service')->all());
        $this->assertSame('Cebu Auto Care', $logs->last()->performed_by);
        $this->assertSame('INV-6200', $logs->last()->invoice_number);
        $this->assertNotNull($logs->last()->maintenance_id);

        // The log is on its own page, not on the preventive-maintenance page.
        $this->actingAs($manager)->get(route('maintenance-manager.preventive-maintenance', ['vehicle_id' => $vehicle->id]))
            ->assertOk()->assertDontSee('INV-1000')->assertDontSee('id="maintenance-log"', false);

        $html = $this->actingAs($manager)->get(route('maintenance-manager.maintenance-logs', ['vehicle_id' => $vehicle->id]))
            ->assertOk()->getContent();
        $this->assertStringContainsString('INV-1000', $html);
        $this->assertStringContainsString('INV-6200', $html);
        $this->assertLessThan(strpos($html, 'INV-1000'), strpos($html, 'INV-6200'), 'newest first');
    }

    public function test_only_the_maintenance_manager_can_open_the_maintenance_logs()
    {
        $this->actingAs($this->userWithRole('maintenance_manager'))->get(route('maintenance-manager.maintenance-logs'))->assertOk();

        foreach (['admin', 'driver_manager', 'commuter'] as $role) {
            $this->actingAs($this->userWithRole($role))->get(route('maintenance-manager.maintenance-logs'))->assertStatus(403);
        }
        $this->actingAs($this->driver(true)[0])->get(route('maintenance-manager.maintenance-logs'))->assertStatus(403);
        auth()->logout();
        $this->get(route('maintenance-manager.maintenance-logs'))->assertRedirect(route('login'));
    }

    public function test_the_maintenance_manager_menu_links_to_the_logs()
    {
        $routes = collect(config('menu.menus.maintenance_manager'))->pluck('route')->filter()->all();

        $this->assertContains('maintenance-manager.maintenance-logs', $routes);
    }

    public function test_toasts_have_no_icon_and_sit_at_the_top()
    {
        $html = $this->get(route('login'))->assertOk()->getContent();

        $this->assertStringNotContainsString('app-toast__icon', $html);
        $this->assertMatchesRegularExpression('/\.app-toast-stack \{[^}]*top: 1\.5rem/s', $html);
        $this->assertDoesNotMatchRegularExpression('/\.app-toast-stack \{[^}]*bottom:/s', $html);
    }

    public function test_the_back_link_is_part_of_the_page_flow_on_every_auth_page()
    {
        foreach (['login', 'register', 'driver.register.page', 'password.request'] as $route) {
            $html = $this->get(route($route))->assertOk()->getContent();

            $this->assertStringNotContainsString('flex absolute top-3 left-3', $html, "$route: the back link must not float over the form");
            // The shell renders the back link in the top bar of the form column, in normal flow.
            $this->assertStringContainsString('class="au-back"', $html, "$route: the back link is part of the page flow");
        }
    }

    public function test_a_rejected_service_is_not_logged()
    {
        $manager = $this->userWithRole('maintenance_manager');
        $vehicle = Vehicle::factory()->create(['status' => 'active']);
        $task = MaintenanceTask::create(['tasks_performed' => 'Brakes', 'miles_between_service' => 5000, 'months_between_service' => 6]);

        $this->actingAs($manager)->post(route('maintenance-manager.preventive-maintenance.store'), [
            'vehicle_id' => $vehicle->id, 'task_id' => $task->id, 'last_service_odo' => 5000,
            'last_service_date' => '2026-09-01', 'last_service_cost' => 10,
        ])->assertSessionHas('success');

        // An odometer lower than the last reading is refused, and so is its log entry.
        $this->actingAs($manager)->post(route('maintenance-manager.preventive-maintenance.store'), [
            'vehicle_id' => $vehicle->id, 'task_id' => $task->id, 'last_service_odo' => 100,
            'last_service_date' => '2026-10-01', 'last_service_cost' => 10,
        ])->assertSessionHasErrors('last_service_odo');

        $this->assertSame(1, VehicleMaintenanceLog::where('vehicle_id', $vehicle->id)->count());
    }

    // ───────────── 4. dev tools ─────────────

    public function test_dev_tools_are_for_the_local_admin_only()
    {
        $this->app['env'] = 'local';

        $admin = $this->userWithRole('admin');
        $commuter = $this->commuter();

        $this->actingAs($admin)->get(route('map'))->assertOk()->assertSee('Dev Tools');
        $this->actingAs($commuter)->get(route('map'))->assertOk()
            ->assertDontSee('Dev Tools')
            ->assertDontSee('id="dev-marker-form"', false);
    }

    // ───────────── 5. view driver: timekeeping and violations ─────────────

    public function test_the_view_driver_modal_loads_timekeeping_and_violations()
    {
        [$user, $driver] = $this->driver(false);
        TimeKeeping::create(['driver_id' => $driver->id, 'date' => today()->toDateString(), 'time_in' => '08:00 AM', 'time_out' => '04:00 PM', 'hours_worked' => 8, 'overtime_hours' => 0]);
        $this->code = ViolationCode::factory()->create();
        ViolationLog::factory()->create(['user_id' => $user->id, 'vc_id' => $this->code->id, 'violation_fine' => '500.00', 'violation_instance' => '1']);
        ViolationLog::factory()->create(['user_id' => $user->id, 'vc_id' => $this->code->id, 'violation_fine' => '300.00', 'violation_instance' => '2', 'payment_status' => 'paid', 'paid_at' => now()]);

        $this->actingAs($this->userWithRole('admin'))
            ->getJson(route('drivers.records', $driver))
            ->assertOk()
            ->assertJsonPath('timekeeping.0.time_in', '08:00 AM')
            ->assertJsonPath('timekeeping_summary.days', 1)
            ->assertJsonPath('violation_summary.count', 2)
            ->assertJsonPath('violation_summary.issued', 800)
            ->assertJsonPath('violation_summary.outstanding', 500);

        $this->actingAs($this->userWithRole('driver_manager'))
            ->getJson(route('drivers.records', $driver))->assertOk();

        $this->actingAs($this->commuter())->getJson(route('drivers.records', $driver))->assertForbidden();
    }

    public function test_the_drivers_page_wires_the_modal_to_the_records()
    {
        [, $driver] = $this->driver(false);

        $html = $this->actingAs($this->userWithRole('admin'))->get(route('drivers.index'))->assertOk()->getContent();

        $this->assertStringContainsString(route('drivers.records', $driver, false), str_replace('\\/', '/', $html));
        $this->assertStringContainsString('Timekeeping', $html);
        $this->assertStringContainsString('Violations', $html);
    }

    // ───────────── 6. violation payment status ─────────────

    protected function violation(User $driverUser, string $fine = '1000.00'): ViolationLog
    {
        // One code is enough (the factory's random codes run out of unique values on a busy dev database).
        $this->code ??= ViolationCode::factory()->create();

        return ViolationLog::factory()->create([
            'user_id' => $driverUser->id,
            'vc_id' => $this->code->id,
            'violation_fine' => $fine,
            'violation_instance' => '1',
        ]);
    }

    public function test_a_new_violation_is_unpaid()
    {
        [$user] = $this->driver(false);

        $this->assertSame('unpaid', $this->violation($user)->fresh()->payment_status);
    }

    public function test_marking_a_fine_paid_takes_it_off_the_outstanding_balance()
    {
        [$user] = $this->driver(false);
        $first = $this->violation($user, '1000.00');
        $this->violation($user, '500.00');
        $manager = $this->userWithRole('driver_manager');

        $before = $this->actingAs($user)->get(route('driver.violations'))->assertOk();
        $this->assertEquals(1500.0, $before->viewData('totalFines'));

        $this->actingAs($manager)
            ->patch(route('driver-manager.violations-log.payment', $first), ['payment_status' => 'paid'])
            ->assertSessionHas('success');

        $first->refresh();
        $this->assertSame('paid', $first->payment_status);
        $this->assertNotNull($first->paid_at);
        $this->assertEquals(1000.0, (float) $first->violation_fine, 'the fine itself stays on record');

        $after = $this->actingAs($user)->get(route('driver.violations'))->assertOk();
        $this->assertEquals(500.0, $after->viewData('totalFines'));
        $this->assertEquals(1000.0, $after->viewData('paidFines'));
        $this->assertSame(2, $after->viewData('totalViolations'));

        $paid = collect($after->viewData('violations')->items())->firstWhere('id', $first->id);
        $this->assertSame('paid', $paid['paymentStatus']);
        $this->assertEquals(0.0, $paid['outstanding']);
    }

    public function test_a_paid_fine_can_be_reopened_and_the_balance_returns()
    {
        [$user] = $this->driver(false);
        $violation = $this->violation($user, '700.00');
        $manager = $this->userWithRole('driver_manager');

        $this->actingAs($manager)->patch(route('driver-manager.violations-log.payment', $violation), ['payment_status' => 'paid']);
        $this->actingAs($manager)->patch(route('driver-manager.violations-log.payment', $violation), ['payment_status' => 'unpaid'])
            ->assertSessionHas('success');

        $violation->refresh();
        $this->assertSame('unpaid', $violation->payment_status);
        $this->assertNull($violation->paid_at);
        $this->assertEquals(700.0, $this->actingAs($user)->get(route('driver.violations'))->viewData('totalFines'));
    }

    public function test_setting_the_status_it_already_has_is_refused()
    {
        [$user] = $this->driver(false);
        $violation = $this->violation($user);

        $this->actingAs($this->userWithRole('driver_manager'))
            ->patch(route('driver-manager.violations-log.payment', $violation), ['payment_status' => 'unpaid'])
            ->assertSessionHas('error');
    }

    public function test_an_invalid_payment_status_is_rejected()
    {
        [$user] = $this->driver(false);
        $violation = $this->violation($user);

        $this->actingAs($this->userWithRole('driver_manager'))
            ->patch(route('driver-manager.violations-log.payment', $violation), ['payment_status' => 'waived'])
            ->assertSessionHasErrors('payment_status');

        $this->assertSame('unpaid', $violation->fresh()->payment_status);
    }

    public function test_only_the_driver_manager_can_mark_a_fine_paid()
    {
        [$user] = $this->driver(false);
        $violation = $this->violation($user);

        foreach ([$user, $this->userWithRole('admin'), $this->commuter()] as $outsider) {
            $this->actingAs($outsider)
                ->patch(route('driver-manager.violations-log.payment', $violation), ['payment_status' => 'paid'])
                ->assertStatus(403);
        }

        $this->assertSame('unpaid', $violation->fresh()->payment_status);
    }

    public function test_marking_a_fine_paid_is_audited()
    {
        [$user] = $this->driver(false);
        $violation = $this->violation($user);

        $this->actingAs($this->userWithRole('driver_manager'))
            ->patch(route('driver-manager.violations-log.payment', $violation), ['payment_status' => 'paid']);

        $entry = Activity::where('subject_type', ViolationLog::class)->where('subject_id', $violation->id)->latest('id')->first();
        $this->assertNotNull($entry);
        $this->assertStringContainsString('as paid', $entry->description);
    }

    public function test_the_violation_log_shows_the_payment_status_and_the_action()
    {
        [$user] = $this->driver(false);
        $this->violation($user);

        $html = $this->actingAs($this->userWithRole('driver_manager'))->get(route('driver-manager.violations-log'))->assertOk()->getContent();

        $this->assertStringContainsString('Unpaid', $html);
        $this->assertStringContainsString('Mark Paid', $html);
    }

    // ───────────── 8. no duplicate error toast ─────────────

    public function test_a_validation_toast_lists_what_the_form_already_shows_so_the_page_can_skip_it()
    {
        $bag = (new \Illuminate\Support\ViewErrorBag)->put('default', new \Illuminate\Support\MessageBag([
            'email' => ['The email field must be a valid email address.'],
        ]));

        $html = $this->withSession(['errors' => $bag])->get(route('register'))->assertOk()->getContent();

        // @json escapes quotes as \u0022 inside the page, so look for the key itself.
        $this->assertStringContainsString('covers', $html);
        $this->assertStringContainsString('alreadyOnPage', $html);
        $this->assertStringContainsString('The email field must be a valid email address.', $html);
    }

    // ───────────── 9. the navigation sidebar starts open ─────────────

    public function test_the_navigation_sidebar_starts_expanded()
    {
        $html = $this->actingAs($this->userWithRole('admin'))->get(route('dashboard'))->assertOk()->getContent();

        // Expanded on desktop widths; tablets (< 1100px) start on the slim icon rail so the page keeps its room.
        $this->assertMatchesRegularExpression("/Alpine\.store\('sidebar', \{[^}]*?open: window\.innerWidth >= 1100/s", $html);
    }

    // ───────────── verification email ─────────────

    public function test_registering_sends_the_verification_email_and_survives_a_mail_failure()
    {
        \Illuminate\Support\Facades\Notification::fake();
        $this->post(route('users.register'), ['email' => 'new1@example.com', 'password' => 'password1', 'confirm-password' => 'password1', 'terms' => 'on'])
            ->assertRedirect(route('verification.notice'));
        \Illuminate\Support\Facades\Notification::assertSentTo(User::where('email', 'new1@example.com')->first(), \App\Notifications\VerifyEmailWithCode::class);

        // A broken mail server must not turn the sign-up into an error page.
        auth()->logout();
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Registered::class, fn () => throw new \RuntimeException('smtp down'));
        $this->post(route('users.register'), ['email' => 'new2@example.com', 'password' => 'password1', 'confirm-password' => 'password1', 'terms' => 'on'])
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('error');
        $this->assertNotNull(User::where('email', 'new2@example.com')->first());
    }

    public function test_signing_in_unverified_sends_a_fresh_verification_email_once()
    {
        \Illuminate\Support\Facades\Notification::fake();
        $user = User::factory()->unverified()->create(['password' => bcrypt('password1')])->assignRole('commuter');

        $this->post(route('users.login'), ['email' => $user->email, 'password' => 'password1'])->assertRedirect(route('verification.notice'));
        \Illuminate\Support\Facades\Notification::assertSentToTimes($user, \App\Notifications\VerifyEmailWithCode::class, 1);

        // A second sign-in straight away does not send another.
        auth()->logout();
        $this->post(route('users.login'), ['email' => $user->email, 'password' => 'password1']);
        \Illuminate\Support\Facades\Notification::assertSentToTimes($user, \App\Notifications\VerifyEmailWithCode::class, 1);
    }

    protected function verifyUrl(User $user, ?string $hash = null): string
    {
        return \Illuminate\Support\Facades\URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => $hash ?? sha1($user->getEmailForVerification()),
        ]);
    }

    public function test_the_email_link_verifies_without_a_session_and_shows_the_close_this_page_screen()
    {
        $user = User::factory()->unverified()->create()->assignRole('commuter');

        $this->get($this->verifyUrl($user))
            ->assertOk()
            ->assertSee('Email verified')
            ->assertSee('You can now close this page');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());

        // Opening it again just says so.
        $this->get($this->verifyUrl($user))->assertOk()->assertSee('already verified');
    }

    public function test_a_tampered_or_unsigned_verification_link_verifies_nothing()
    {
        $user = User::factory()->unverified()->create()->assignRole('commuter');

        $this->get($this->verifyUrl($user, sha1('someone-else@example.com')))->assertForbidden();
        $this->get('/email/verify/'.$user->id.'/'.sha1($user->getEmailForVerification()))->assertForbidden();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
}
