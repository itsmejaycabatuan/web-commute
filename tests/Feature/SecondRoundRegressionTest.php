<?php

namespace Tests\Feature;

use App\Events\LocationUpdated;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Driver;
use App\Models\MaintenanceTask;
use App\Models\Payment;
use App\Models\TopupHistory;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleLocation;
use App\Models\ViolationCode;
use App\Models\ViolationLog;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Second round of manual-test findings.
 */
class SecondRoundRegressionTest extends TestCase
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

    protected function userWithRole(string $role, array $attributes = []): User
    {
        return User::factory()->create(array_merge(['email_verified_at' => now()], $attributes))->assignRole($role);
    }

    // ───────────────── 1. ngrok / reverse proxy ─────────────────

    public function test_urls_follow_the_scheme_and_host_the_proxy_forwards()
    {
        // ngrok terminates https and forwards plain http with X-Forwarded-*.
        $response = $this->call('GET', '/login', [], [], [], [
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_HOST' => 'demo.ngrok-free.app',
            'HTTP_X_FORWARDED_PORT' => '443',
            'REMOTE_ADDR' => '203.0.113.9',
        ]);

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('https://demo.ngrok-free.app/login', $html);
        $this->assertStringNotContainsString('http://demo.ngrok-free.app', $html);
    }

    public function test_the_ajax_helper_posts_to_the_page_s_own_origin()
    {
        $html = view('components.toast', ['errors' => new \Illuminate\Support\ViewErrorBag])->render();

        $this->assertStringContainsString('function sameOrigin', $html);
        $this->assertStringContainsString('action = sameOrigin(action)', $html);
    }

    // ───────────────── 2. live marker follows driver status ─────────────────

    protected function broadcastingDriver(string $status): array
    {
        $user = $this->userWithRole('driver');
        $driver = Driver::factory()->create([
            'user_id' => $user->id, 'is_approved' => 1, 'is_rejected' => 0, 'status' => $status,
        ]);
        $vehicle = Vehicle::factory()->create(['status' => 'active', 'driver_id' => $driver->id]);

        VehicleLocation::create([
            'vehicle_id' => $vehicle->id, 'user_id' => $user->id, 'latitude' => 10.3157,
            'longitude' => 123.8854, 'last_update' => now(),
        ]);

        return [$user, $driver, $vehicle];
    }

    public function test_the_active_vehicle_feed_carries_the_driver_s_status()
    {
        [, , $available] = $this->broadcastingDriver('active');
        [, , $unavailable] = $this->broadcastingDriver('inactive');

        $vehicles = collect($this->getJson('/track/vehicles/active')->assertOk()->json('vehicles'));

        $this->assertSame('active', $vehicles->firstWhere('id', $available->id)['driver_status']);
        $this->assertSame('inactive', $vehicles->firstWhere('id', $unavailable->id)['driver_status']);
    }

    public function test_the_feed_follows_the_driver_when_they_toggle_their_status()
    {
        [$user, $driver, $vehicle] = $this->broadcastingDriver('inactive');

        $this->actingAs(User::find($user->id))
            ->post(route('driver.status.update'), ['status' => 'active'])
            ->assertSessionHas('success');

        // A driver's own map never lists the fleet, so read the feed as a commuter.
        $vehicles = collect($this->actingAs($this->userWithRole('commuter'))->getJson('/track/vehicles/active')->json('vehicles'));
        $this->assertSame('active', $vehicles->firstWhere('id', $vehicle->id)['driver_status']);
    }

    public function test_the_broadcast_event_carries_the_driver_status()
    {
        $event = new LocationUpdated('7', 10.3, 123.9, null, null, 1, 'active');

        $this->assertSame('active', $event->broadcastWith()['driver_status']);
    }

    public function test_the_map_colours_markers_and_labels_the_popup_by_driver_status()
    {
        $html = $this->actingAs($this->userWithRole('commuter'))->get(route('map'))->assertOk()->getContent();

        $this->assertStringContainsString('vehicle-status-active', $html);
        $this->assertStringContainsString('vehicle-status-inactive', $html);
        $this->assertStringContainsString('Driver status', $html);
    }

    // ───────────────── 3. modal input rules ─────────────────

    public function test_the_admin_modals_carry_the_registration_rules()
    {
        $admin = $this->userWithRole('admin');

        $drivers = $this->actingAs($admin)->get(route('drivers.index'))->assertOk()->getContent();
        foreach (['required|email|max:255', 'required|min:8', 'required|same:password', 'required|phone', 'required|image:2048', 'data-format="phone"', '+63'] as $needle) {
            $this->assertStringContainsString($needle, $drivers, "drivers modal is missing {$needle}");
        }

        $commuters = $this->actingAs($admin)->get(route('commuters.index'))->assertOk()->getContent();
        foreach (['required|email|max:255', 'required|min:8', 'required|same:password'] as $needle) {
            $this->assertStringContainsString($needle, $commuters, "commuters modal is missing {$needle}");
        }

        $this->actingAs($admin)->get(route('staff.index'))->assertOk()->assertSee('required|same:password', false);
    }

    public function test_the_admin_driver_form_rejects_a_bad_phone_and_a_non_image_licence()
    {
        Storage::fake('public');
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->postJson(route('drivers.store'), [
            'name' => 'Phone Test', 'email' => 'phone.test@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'license_number' => 'PH-1', 'license_code' => 'PRO', 'expiration_date' => '2030-01-01',
            'contact_info' => '12345',
            'license_image' => UploadedFile::fake()->create('license.pdf', 100, 'application/pdf'),
        ])->assertStatus(422)->assertJsonValidationErrors(['contact_info', 'license_image']);
    }

    public function test_the_admin_driver_form_accepts_a_registration_style_phone_number()
    {
        Storage::fake('public');
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->postJson(route('drivers.store'), [
            'name' => 'Phone Ok', 'email' => 'phone.ok@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'license_number' => 'PH-2', 'license_code' => 'PRO', 'expiration_date' => '2030-01-01',
            'contact_info' => '917 123 4567',
            'license_image' => UploadedFile::fake()->image('license.png'),
        ])->assertOk();

        $this->assertDatabaseHas('users', ['email' => 'phone.ok@example.com']);
    }

    public function test_the_commuter_form_words_a_password_mismatch_like_registration()
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->postJson(route('commuters.store'), [
            'email' => 'mismatch@example.com', 'password' => 'password123', 'password_confirmation' => 'different',
        ])->assertStatus(422)->assertJsonPath('errors.password.0', 'The password confirmation does not match.');
    }

    // ───────────────── 4 + 6. licence notices ─────────────────

    public function test_the_view_driver_modal_knows_the_licence_state()
    {
        $admin = $this->userWithRole('admin');
        $driver = Driver::factory()->approved()->create(['expiration_date' => now()->subDays(3)->toDateString()]);

        $response = $this->actingAs($admin)->get(route('drivers.index'))->assertOk();
        $response->assertSee('viewLicenseState', false);

        $row = $response->viewData('drivers')->firstWhere('id', $driver->id);
        $this->assertSame('expired', $row->licenseState());
    }

    protected function driverWithLicence(?string $expires): User
    {
        $user = $this->userWithRole('driver');
        Driver::factory()->create([
            'user_id' => $user->id, 'is_approved' => 1, 'is_rejected' => 0, 'expiration_date' => $expires,
        ]);

        return $user;
    }

    public function test_a_driver_with_an_expired_licence_is_warned_at_every_login()
    {
        $user = $this->driverWithLicence(now()->subDays(4)->toDateString());

        foreach ([1, 2] as $attempt) {
            $this->post(route('users.login'), ['email' => $user->email, 'password' => '123456789'])
                ->assertRedirect(route('map'))
                ->assertSessionHas('warning', fn ($message) => str_contains($message, 'expired'));

            $this->post(route('users.logout'));
        }
    }

    public function test_a_driver_whose_licence_is_expiring_is_told_how_many_days_are_left()
    {
        $user = $this->driverWithLicence(now()->addDays(12)->toDateString());

        $this->post(route('users.login'), ['email' => $user->email, 'password' => '123456789'])
            ->assertSessionHas('warning', fn ($message) => str_contains($message, 'expires in 12 days'));
    }

    public function test_a_driver_with_a_valid_licence_gets_no_warning()
    {
        $user = $this->driverWithLicence(now()->addDays(200)->toDateString());

        $this->post(route('users.login'), ['email' => $user->email, 'password' => '123456789'])
            ->assertRedirect(route('map'))
            ->assertSessionMissing('warning');
    }

    public function test_commuters_are_never_given_a_licence_warning()
    {
        $user = $this->userWithRole('commuter');

        $this->post(route('users.login'), ['email' => $user->email, 'password' => '123456789'])
            ->assertSessionMissing('warning');
    }

    // ───────────────── 5. pagination ─────────────────

    public function test_the_client_filtered_lists_ship_a_ten_per_page_pager()
    {
        $admin = $this->userWithRole('admin');
        $manager = $this->userWithRole('maintenance_manager');
        $driverManager = $this->userWithRole('driver_manager');

        $this->actingAs($admin)->get(route('drivers.index'))->assertOk()->assertSee('pageOf(filteredDrivers)', false);
        $this->actingAs($admin)->get(route('commuters.index'))->assertOk()->assertSee('pageOf(filteredCommuters)', false);
        $this->actingAs($manager)->get(route('vehicles.index'))->assertOk()->assertSee('pageOf(filtered)', false);
        $this->actingAs($driverManager)->get(route('driver-manager.violation-codes'))->assertOk()->assertSee('pageOf(violations)', false);
    }

    public function test_the_violation_log_pages_ten_at_a_time_and_keeps_the_filters()
    {
        $manager = $this->userWithRole('driver_manager');
        $driver = $this->userWithRole('driver', ['email' => 'paged.driver@example.com']);
        Driver::factory()->create(['user_id' => $driver->id, 'name' => 'Paged Driver']);
        $code = ViolationCode::factory()->create();

        foreach (range(1, 13) as $i) {
            ViolationLog::create([
                'user_id' => $driver->id, 'vc_id' => $code->id, 'violation_instance' => '1', 'violation_fine' => 100,
                'date_of_violation' => now()->toDateString(), 'time_of_violation' => '08:00:00',
                'place_of_violation' => 'Street '.$i,
            ]);
        }

        $first = $this->actingAs($manager)->get(route('driver-manager.violations-log', ['search' => 'Paged Driver']))->assertOk();
        $this->assertSame(10, $first->viewData('violations')->perPage());
        $this->assertCount(10, $first->viewData('violations'));
        $this->assertSame(13, $first->viewData('violations')->total());
        $first->assertSee('search=Paged', false);

        $second = $this->actingAs($manager)->get(route('driver-manager.violations-log', ['search' => 'Paged Driver', 'page' => 2]))->assertOk();
        $this->assertCount(3, $second->viewData('violations'));
    }

    public function test_the_maintenance_task_list_pages_ten_at_a_time_but_counts_all_of_them()
    {
        $manager = $this->userWithRole('maintenance_manager');
        $existing = MaintenanceTask::count();

        foreach (range(1, 12) as $i) {
            MaintenanceTask::create(['tasks_performed' => 'Zz Task '.$i, 'miles_between_service' => 1000, 'months_between_service' => null]);
        }

        $response = $this->actingAs($manager)->get(route('maintenance-manager.maintenance-tasks'))->assertOk();

        $this->assertSame(10, $response->viewData('tasks')->perPage());
        $this->assertSame($existing + 12, $response->viewData('stats')['total']);
    }

    public function test_the_driver_violation_record_pages_ten_at_a_time_with_full_totals()
    {
        $user = $this->userWithRole('driver');
        Driver::factory()->create(['user_id' => $user->id, 'is_approved' => 1, 'is_rejected' => 0]);
        $code = ViolationCode::factory()->create();

        foreach (range(1, 12) as $i) {
            ViolationLog::create([
                'user_id' => $user->id, 'vc_id' => $code->id, 'violation_instance' => '1', 'violation_fine' => 50,
                'date_of_violation' => now()->toDateString(), 'time_of_violation' => '08:00:00',
                'place_of_violation' => 'Road '.$i,
            ]);
        }

        $response = $this->actingAs($user)->get(route('driver.violations'))->assertOk();

        $this->assertCount(10, $response->viewData('violations'));
        $this->assertSame(12, $response->viewData('totalViolations'));
        $this->assertEquals(600.0, $response->viewData('totalFines'));
    }

    public function test_the_commuter_detail_page_pages_trips_and_topups()
    {
        $admin = $this->userWithRole('admin');
        $commuter = $this->userWithRole('commuter');
        $wallet = Wallet::firstOrCreate(['user_id' => $commuter->id]);

        Payment::factory()->count(12)->create(['paid_by' => $commuter->id, 'status' => 'paid', 'paid_at' => now()]);
        foreach (range(1, 11) as $i) {
            TopupHistory::create(['user_id' => $commuter->id, 'wallet_id' => $wallet->id, 'amount_added' => 10 + $i, 'payment_method' => 'gcash']);
        }

        $response = $this->actingAs($admin)->get(route('commuters.show', $commuter))->assertOk();

        $this->assertCount(10, $response->viewData('trips'));
        $this->assertCount(10, $response->viewData('topups'));
        $this->assertSame(12, $response->viewData('trips')->total());
        $this->assertSame(11, $response->viewData('topups')->total());
    }

    // ───────────────── 7. one receipt ─────────────────

    public function test_the_old_admin_receipt_route_is_gone()
    {
        $this->assertFalse(Route::has('admin.receipt.show'));
        $this->assertFileDoesNotExist(resource_path('views/admin/commuters/receipt.blade.php'));
    }

    public function test_an_admin_opens_any_payment_through_the_payment_receipt_route()
    {
        $admin = $this->userWithRole('admin');
        $commuter = $this->userWithRole('commuter', ['email' => 'receipt.owner@example.com']);
        $paid = Payment::factory()->create(['paid_by' => $commuter->id, 'status' => 'paid', 'paid_at' => now()]);
        $failed = Payment::factory()->create(['paid_by' => $commuter->id, 'status' => 'failed', 'paid_at' => null]);

        $this->actingAs($admin)->get(route('payment.showReceipt', $paid->id))
            ->assertOk()
            ->assertSee('receipt.owner@example.com')
            ->assertSee('Settled')
            ->assertSee('Back to Transactions')
            ->assertSee(route('faretransactions'), false);

        $this->actingAs($admin)->get(route('payment.showReceipt', $failed->id))
            ->assertOk()
            ->assertSee('failed');
    }

    public function test_a_commuter_still_only_sees_their_own_settled_receipts()
    {
        $owner = $this->userWithRole('commuter');
        $other = $this->userWithRole('commuter');
        $payment = Payment::factory()->create(['paid_by' => $owner->id, 'status' => 'paid', 'paid_at' => now()]);
        $failed = Payment::factory()->create(['paid_by' => $owner->id, 'status' => 'failed', 'paid_at' => null]);

        $this->actingAs($owner)->get(route('payment.showReceipt', $payment->id))
            ->assertOk()->assertSee('Back to History')->assertDontSee('Commuter</p>', false);
        $this->actingAs($other)->get(route('payment.showReceipt', $payment->id))->assertNotFound();
        $this->actingAs($owner)->get(route('payment.showReceipt', $failed->id))->assertRedirect(route('payment.history'));
    }

    public function test_the_transactions_modal_links_to_the_payment_receipt_by_id()
    {
        $admin = $this->userWithRole('admin');
        $commuter = $this->userWithRole('commuter', ['email' => 'ledger.link@example.com']);
        $payment = Payment::factory()->create(['paid_by' => $commuter->id, 'status' => 'paid', 'paid_at' => now()]);

        $this->actingAs($admin)
            ->get(route('faretransactions', ['search' => 'ledger.link']))
            ->assertOk()
            ->assertSee('\u0022id\u0022:'.$payment->id, false)
            ->assertSee(route('payment.showReceipt', '__ID__'), false);
    }

    // ───────────────── 8. toast has one (light) design ─────────────────

    public function test_the_toast_has_no_dark_mode_variant()
    {
        $css = file_get_contents(resource_path('views/components/toast.blade.php'));

        $this->assertStringNotContainsString('.dark .app-toast', $css);
        $this->assertStringNotContainsString('is-dark', $css);
        $this->assertStringContainsString('.app-toast--success', $css);
    }
}
