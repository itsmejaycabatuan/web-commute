<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Driver;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Regression coverage for the issues found during manual testing.
 */
class BugFixRegressionTest extends TestCase
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

    /** @return array{0: User, 1: Driver} */
    protected function driverUser(array $driver = []): array
    {
        $user = $this->userWithRole('driver');
        $profile = Driver::factory()->create(array_merge(['user_id' => $user->id], $driver));

        return [$user, $profile];
    }

    // ───────────────────── 1. driver registration message ─────────────────────

    public function test_a_registered_driver_is_told_to_wait_for_approval()
    {
        Storage::fake('public');

        $this->post(route('driver.register'), [
            'name' => 'Pat Driver',
            'email' => 'pat.driver@example.com',
            'contact_info' => '09171234567',
            'password' => 'password123',
            'confirm-password' => 'password123',
            'terms' => '1',
            'license_image' => UploadedFile::fake()->image('license.jpg'),
        ])->assertRedirect(route('login'))->assertSessionHas('driver_registered', true);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('registered')
            ->assertSee('approve');

        $driver = User::where('email', 'pat.driver@example.com')->first()->driver;
        $this->assertFalse((bool) $driver->is_approved);
        $this->assertMatchesRegularExpression('/^DRV-\d{6}$/', $driver->driver_code);
    }

    // ───────────────────────── 6. password change ─────────────────────────

    public function test_the_new_password_may_not_be_the_current_one()
    {
        $user = $this->userWithRole('driver');

        $this->actingAs($user)
            ->put(route('settings.password'), [
                'current_password' => '123456789',
                'password' => '123456789',
                'password_confirmation' => '123456789',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue(\Hash::check('123456789', $user->fresh()->password));
    }

    public function test_the_commuter_password_form_also_rejects_an_unchanged_password()
    {
        $user = $this->userWithRole('commuter');

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'current_password' => '123456789',
                'password' => '123456789',
                'password_confirmation' => '123456789',
            ])
            ->assertSessionHasErrors('password');
    }

    // ─────────────────────── 7. sign out other devices ───────────────────────

    public function test_a_commuter_can_sign_out_other_devices_with_their_password()
    {
        $user = $this->userWithRole('commuter');

        $this->actingAs($user)
            ->post(route('settings.logout-others'), ['current_password' => '123456789'])
            ->assertSessionHas('success');
    }

    public function test_signing_out_other_devices_needs_the_correct_password()
    {
        $user = $this->userWithRole('commuter');

        $this->actingAs($user)
            ->post(route('settings.logout-others'), ['current_password' => 'wrong'])
            ->assertSessionHasErrors('current_password');
    }

    public function test_other_database_sessions_are_removed_and_the_current_one_kept()
    {
        config(['session.driver' => 'database']);
        $user = $this->userWithRole('commuter');

        foreach (['other-a', 'other-b', 'mine'] as $id) {
            DB::table('sessions')->insert([
                'id' => $id, 'user_id' => $user->id, 'ip_address' => '127.0.0.1',
                'user_agent' => 'test', 'payload' => '', 'last_activity' => time(),
            ]);
        }

        $this->assertSame(2, $user->forgetOtherSessions('mine'));
        $this->assertSame(['mine'], DB::table('sessions')->where('user_id', $user->id)->pluck('id')->all());
    }

    // ───────────────────────── 8. driver e-mail ─────────────────────────

    public function test_a_driver_cannot_change_their_email_address()
    {
        [$user] = $this->driverUser();
        $original = $user->email;

        $this->actingAs($user)
            ->put(route('settings.update'), ['email' => 'new.address@example.com'])
            ->assertSessionHas('error');

        $this->assertSame($original, $user->fresh()->email);
    }

    public function test_other_roles_can_still_change_their_email_address()
    {
        $user = $this->userWithRole('commuter');

        $this->actingAs($user)
            ->put(route('settings.update'), ['email' => 'fresh.address@example.com'])
            ->assertSessionHas('success');

        $this->assertSame('fresh.address@example.com', $user->fresh()->email);
    }

    // ───────────────── 9. suspension applies to a live session ─────────────────

    public function test_a_driver_suspended_mid_session_is_signed_out_on_the_next_request()
    {
        [$user, $driver] = $this->driverUser(['is_approved' => 1, 'is_rejected' => 0]);

        $this->actingAs($user)->get(route('driver.timekeeping'))->assertOk();

        $driver->suspend('Under investigation');

        // A real request loads the user afresh; do the same here.
        $this->actingAs(User::find($user->id))
            ->get(route('driver.timekeeping'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('credentials');

        $this->assertGuest();
    }

    public function test_a_suspended_session_gets_json_not_a_page_for_ajax_calls()
    {
        [$user, $driver] = $this->driverUser(['is_approved' => 1, 'is_rejected' => 0]);
        $driver->suspend();

        $this->actingAs(User::find($user->id))
            ->postJson(route('driver.status.update'), ['status' => 'active'])
            ->assertForbidden()
            ->assertJsonPath('redirect', route('login'));
    }

    public function test_a_commuter_suspended_mid_session_is_signed_out_too()
    {
        $user = $this->userWithRole('commuter');
        $user->suspend();

        $this->actingAs($user)->get(route('payment.history'))->assertRedirect(route('login'));
    }

    // ─────────────── 6b. broadcasting needs an active vehicle ───────────────

    public function test_a_driver_without_a_vehicle_cannot_broadcast()
    {
        [$user] = $this->driverUser(['is_approved' => 1, 'is_rejected' => 0]);
        $vehicle = Vehicle::factory()->create(['status' => 'active']);

        $this->actingAs($user)
            ->postJson(route('vehicle.broadcast'), $this->broadcastPayload($user, $vehicle))
            ->assertForbidden();

        $this->assertSame(0, VehicleLocation::where('vehicle_id', $vehicle->id)->count());
    }

    public function test_a_driver_on_an_inactive_vehicle_cannot_broadcast()
    {
        [$user, $driver] = $this->driverUser(['is_approved' => 1, 'is_rejected' => 0]);
        $vehicle = Vehicle::factory()->withDriver($driver->id)->create(['status' => 'inactive']);

        $this->actingAs($user)
            ->postJson(route('vehicle.broadcast'), $this->broadcastPayload($user, $vehicle))
            ->assertForbidden()
            ->assertJsonPath('message', 'This vehicle is not in service.');
    }

    protected function broadcastPayload(User $user, Vehicle $vehicle): array
    {
        return [
            'vehicle_id' => (string) $vehicle->id,
            'latitude' => 10.3157,
            'longitude' => 123.8854,
            'user_id' => $user->id,
        ];
    }

    // ───────────────────────── 10. staff CRUD ─────────────────────────

    public function test_an_admin_can_create_edit_suspend_and_delete_staff()
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->post(route('staff.store'), [
            'email' => 'new.manager@example.com',
            'role' => 'driver_manager',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('staff.index'))->assertSessionHas('success');

        $staff = User::where('email', 'new.manager@example.com')->firstOrFail();
        $this->assertTrue($staff->hasRole('driver_manager'));
        $this->assertNotNull($staff->email_verified_at);

        $this->actingAs($admin)->put(route('staff.update', $staff), [
            'email' => 'renamed.manager@example.com',
            'role' => 'maintenance_manager',
        ])->assertSessionHas('success');

        $staff->refresh();
        $this->assertSame('renamed.manager@example.com', $staff->email);
        $this->assertTrue($staff->hasRole('maintenance_manager'));
        $this->assertFalse($staff->hasRole('driver_manager'));

        $this->actingAs($admin)->patch(route('staff.suspension', $staff))->assertSessionHas('success');
        $this->assertTrue($staff->fresh()->is_suspended);

        $this->actingAs($admin)->patch(route('staff.suspension', $staff))->assertSessionHas('success');
        $this->assertFalse($staff->fresh()->is_suspended);

        $this->actingAs($admin)->delete(route('staff.destroy', $staff))->assertSessionHas('success');
        $this->assertDatabaseMissing('users', ['id' => $staff->id]);
    }

    public function test_staff_forms_validate_and_report_errors_per_field()
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->postJson(route('staff.store'), [
            'email' => 'not-an-email',
            'role' => 'admin',
            'password' => 'short',
        ])->assertStatus(422)->assertJsonValidationErrors(['email', 'role', 'password']);
    }

    public function test_only_staff_accounts_are_reachable_through_the_staff_screen()
    {
        $admin = $this->userWithRole('admin');
        $commuter = $this->userWithRole('commuter');

        $this->actingAs($admin)->delete(route('staff.destroy', $commuter))->assertNotFound();
        $this->actingAs($admin)->patch(route('staff.suspension', $commuter))->assertNotFound();
        $this->assertDatabaseHas('users', ['id' => $commuter->id]);
    }

    public function test_the_staff_list_pages_ten_at_a_time()
    {
        $admin = $this->userWithRole('admin');
        User::factory()->count(13)->create()->each->assignRole('driver_manager');

        $response = $this->actingAs($admin)->get(route('staff.index'))->assertOk();
        $this->assertCount(10, $response->viewData('staff'));
    }

    // ──────────── 11 + 17. payment status and richer filters ────────────

    protected function payment(User $user, string $status, array $attributes = []): Payment
    {
        return Payment::factory()->create(array_merge([
            'paid_by' => $user->id,
            'status' => $status,
            'payment_method' => 'GCash',
            'paid_at' => $status === 'paid' ? now() : null,
        ], $attributes));
    }

    public function test_fare_transactions_list_every_status_and_can_be_filtered_by_it()
    {
        $admin = $this->userWithRole('admin');
        $commuter = $this->userWithRole('commuter', ['email' => 'ledger.person@example.com']);

        foreach (['paid', 'pending', 'failed', 'cancelled'] as $status) {
            $this->payment($commuter, $status);
        }

        $all = $this->actingAs($admin)->get(route('faretransactions', ['search' => 'ledger.person']))->assertOk();
        $this->assertSame(['cancelled', 'failed', 'paid', 'pending'], $all->viewData('allTransactions')->pluck('status')->sort()->values()->all());

        $failed = $this->actingAs($admin)->get(route('faretransactions', ['search' => 'ledger.person', 'status' => 'failed']))->assertOk();
        $this->assertSame(['failed'], $failed->viewData('allTransactions')->pluck('status')->all());
    }

    public function test_fare_transactions_filter_by_method_amount_and_date()
    {
        $admin = $this->userWithRole('admin');
        $commuter = $this->userWithRole('commuter', ['email' => 'filter.person@example.com']);

        $cheap = $this->payment($commuter, 'paid', ['price' => 20, 'payment_method' => 'Maya']);
        $dear = $this->payment($commuter, 'paid', ['price' => 500, 'payment_method' => 'GCash']);

        $ids = fn (array $query) => $this->actingAs($admin)
            ->get(route('faretransactions', array_merge(['search' => 'filter.person'], $query)))
            ->assertOk()->viewData('allTransactions')->pluck('id')->all();

        $this->assertSame([$cheap->id], $ids(['method' => 'Maya']));
        $this->assertSame([$dear->id], $ids(['min_amount' => 100]));
        $this->assertSame([$cheap->id], $ids(['max_amount' => 100]));
        $this->assertSame([], $ids(['to_date' => now()->subDays(3)->toDateString()]));
    }

    public function test_fare_transaction_revenue_counts_only_settled_payments()
    {
        $admin = $this->userWithRole('admin');
        $commuter = $this->userWithRole('commuter');
        $before = Payment::settled()->sum('price');

        $this->payment($commuter, 'paid', ['price' => 40]);
        $this->payment($commuter, 'failed', ['price' => 900]);
        $this->payment($commuter, 'pending', ['price' => 900]);

        $response = $this->actingAs($admin)->get(route('faretransactions'))->assertOk();
        $this->assertEquals($before + 40, $response->viewData('totalRevenue'));
    }

    public function test_the_fare_transaction_list_pages_ten_at_a_time()
    {
        $admin = $this->userWithRole('admin');
        $commuter = $this->userWithRole('commuter');
        Payment::factory()->count(12)->create(['paid_by' => $commuter->id, 'status' => 'paid']);

        $response = $this->actingAs($admin)->get(route('faretransactions'))->assertOk();
        $this->assertSame(10, $response->viewData('allTransactions')->perPage());
        $this->assertCount(10, $response->viewData('allTransactions'));
    }

    protected function topup(User $user, string $status, float $amount = 100, string $method = 'gcash'): TopupHistory
    {
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id]);

        return TopupHistory::create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'amount_added' => $amount,
            'payment_method' => $method,
            'status' => $status,
        ]);
    }

    public function test_wallet_topups_list_every_status_and_filter_by_it()
    {
        $admin = $this->userWithRole('admin');
        $commuter = $this->userWithRole('commuter', ['email' => 'topup.person@example.com']);

        $done = $this->topup($commuter, 'completed', 100);
        $pending = $this->topup($commuter, 'pending', 200);
        $failed = $this->topup($commuter, 'failed', 300, 'maya');

        $ids = fn (array $query) => $this->actingAs($admin)
            ->get(route('admin.topups', array_merge(['search' => 'topup.person'], $query)))
            ->assertOk()->viewData('transactions')->pluck('id')->sort()->values()->all();

        $this->assertSame([$done->id, $pending->id, $failed->id], $ids([]));
        $this->assertSame([$pending->id], $ids(['status' => 'pending']));
        $this->assertSame([$failed->id], $ids(['method' => 'maya']));
        $this->assertSame([$failed->id], $ids(['min_amount' => 250]));
    }

    public function test_funds_added_counts_completed_topups_only()
    {
        $admin = $this->userWithRole('admin');
        $commuter = $this->userWithRole('commuter');
        $before = TopupHistory::completed()->sum('amount_added');

        $this->topup($commuter, 'completed', 75);
        $this->topup($commuter, 'failed', 5000);

        $response = $this->actingAs($admin)->get(route('admin.topups'))->assertOk();
        $this->assertEquals($before + 75, $response->viewData('totalFundsAdded'));
    }

    public function test_an_invalid_ledger_filter_is_rejected_not_ignored()
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->get(route('faretransactions', ['status' => 'bogus']))->assertSessionHasErrors('status');
        $this->actingAs($admin)->get(route('admin.topups', ['status' => 'bogus']))->assertSessionHasErrors('status');
    }

    // ───────────────── 12. pending drivers: review only ─────────────────

    public function test_a_pending_driver_can_be_neither_edited_nor_suspended()
    {
        $admin = $this->userWithRole('admin');
        $pending = Driver::factory()->pending()->create(['name' => 'Pending Pat']);

        $this->actingAs($admin)
            ->put(route('drivers.update', ['user' => $pending->id]), [
                'name' => 'Changed', 'license_number' => 'X-1', 'license_code' => 'PRO',
                'expiration_date' => '2030-01-01', 'contact_info' => '0917',
            ])
            ->assertSessionHas('error');

        $this->actingAs($admin)
            ->patch(route('drivers.suspension', ['driver' => $pending->id]))
            ->assertSessionHas('error');

        $pending->refresh();
        $this->assertSame('Pending Pat', $pending->name);
        $this->assertFalse($pending->is_suspended);
    }

    public function test_an_approved_driver_can_still_be_edited_and_suspended()
    {
        $admin = $this->userWithRole('admin');
        $driver = Driver::factory()->approved()->create();

        $this->actingAs($admin)
            ->patch(route('drivers.suspension', ['driver' => $driver->id]))
            ->assertSessionHas('success');

        $this->assertTrue($driver->fresh()->is_suspended);
    }

    // ───────────────────── 13. generated driver codes ─────────────────────

    public function test_driver_codes_are_generated_in_sequence()
    {
        $first = Driver::factory()->create(['driver_code' => null]);
        $second = Driver::factory()->create(['driver_code' => null]);

        $this->assertMatchesRegularExpression('/^DRV-\d{6}$/', $first->driver_code);
        $this->assertSame(
            (int) substr($first->driver_code, 4) + 1,
            (int) substr($second->driver_code, 4)
        );
    }

    public function test_approving_a_driver_ignores_a_typed_driver_code()
    {
        $admin = $this->userWithRole('admin');
        $driver = Driver::factory()->pending()->create();
        $generated = $driver->driver_code;

        $this->actingAs($admin)
            ->post(route('drivers.approve', ['user' => $driver->id]), [
                'license_number' => 'APPR-1', 'license_code' => 'PRO',
                'expiration_date' => '2031-01-01', 'driver_code' => 'HACKED-1',
            ])
            ->assertSessionHas('success');

        $this->assertSame($generated, $driver->fresh()->driver_code);
        $this->assertTrue($driver->fresh()->is_approved);
    }

    // ─────────────── 14 + 22 + 23. vehicles ───────────────

    protected function vehiclePayload(array $overrides = []): array
    {
        return array_merge([
            'year' => 2022, 'brand' => 'Toyota', 'model' => 'Hiace',
            'plate_number' => 'ZZZ-'.random_int(1000, 9999), 'status' => 'active',
            'acquisition_date' => '2022-01-01',
        ], $overrides);
    }

    public function test_the_admin_can_run_the_vehicle_crud()
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->postJson(route('vehicles.store'), $this->vehiclePayload(['plate_number' => 'ADM-0001']))
            ->assertCreated();

        $vehicle = Vehicle::where('plate_number', 'ADM-0001')->firstOrFail();

        $this->actingAs($admin)
            ->deleteJson(route('vehicles.destroy', $vehicle))
            ->assertOk();

        $this->assertDatabaseMissing('vehicles', ['id' => $vehicle->id]);
    }

    public function test_an_inactive_vehicle_cannot_be_given_a_driver()
    {
        $manager = $this->userWithRole('maintenance_manager');
        $driver = Driver::factory()->approved()->create();

        $this->actingAs($manager)
            ->postJson(route('vehicles.store'), $this->vehiclePayload(['status' => 'inactive', 'driver_id' => $driver->id]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('driver_id');

        $vehicle = Vehicle::factory()->create(['status' => 'active']);

        $this->actingAs($manager)
            ->patchJson(route('vehicles.update', $vehicle), $this->vehiclePayload([
                'plate_number' => $vehicle->plate_number, 'status' => 'inactive', 'driver_id' => $driver->id,
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('driver_id');
    }

    public function test_an_active_vehicle_can_be_given_a_driver()
    {
        $manager = $this->userWithRole('maintenance_manager');
        $driver = Driver::factory()->approved()->create();

        $this->actingAs($manager)
            ->postJson(route('vehicles.store'), $this->vehiclePayload(['driver_id' => $driver->id]))
            ->assertCreated();
    }

    public function test_taking_a_vehicle_out_of_service_releases_its_driver()
    {
        $manager = $this->userWithRole('maintenance_manager');
        $driver = Driver::factory()->approved()->create();
        $vehicle = Vehicle::factory()->withDriver($driver->id)->create(['status' => 'active']);

        $this->actingAs($manager)
            ->patchJson(route('vehicles.update', $vehicle), $this->vehiclePayload([
                'plate_number' => $vehicle->plate_number, 'status' => 'inactive', 'driver_id' => $driver->id,
            ]))
            ->assertOk();

        $this->assertSame(0, $vehicle->fresh()->drivers()->count());
    }

    public function test_an_assigned_vehicle_cannot_be_deleted()
    {
        $manager = $this->userWithRole('maintenance_manager');
        $driver = Driver::factory()->approved()->create();
        $vehicle = Vehicle::factory()->withDriver($driver->id)->create(['status' => 'active']);

        $this->actingAs($manager)
            ->deleteJson(route('vehicles.destroy', $vehicle))
            ->assertStatus(409);

        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id]);
    }

    public function test_an_unassigned_vehicle_can_be_deleted()
    {
        $manager = $this->userWithRole('maintenance_manager');
        $vehicle = Vehicle::factory()->create(['status' => 'active']);

        $this->actingAs($manager)->deleteJson(route('vehicles.destroy', $vehicle))->assertOk();
        $this->assertDatabaseMissing('vehicles', ['id' => $vehicle->id]);
    }

    // ───────────────── 15. a driver holding a vehicle stays ─────────────────

    public function test_a_driver_holding_a_vehicle_cannot_delete_their_own_account()
    {
        [$user, $driver] = $this->driverUser(['is_approved' => 1, 'is_rejected' => 0]);
        Vehicle::factory()->withDriver($driver->id)->create(['status' => 'active']);

        $this->actingAs($user)
            ->delete(route('users.delete-account'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    // ──────────────── 16. licence expiry states ────────────────

    public function test_a_licence_is_valid_expiring_or_expired()
    {
        $this->assertSame('valid', Driver::factory()->make(['expiration_date' => now()->addDays(120)->toDateString()])->licenseState());
        $this->assertSame('expiring', Driver::factory()->make(['expiration_date' => now()->addDays(10)->toDateString()])->licenseState());
        $this->assertSame('expiring', Driver::factory()->make(['expiration_date' => now()->toDateString()])->licenseState());
        $this->assertSame('expired', Driver::factory()->make(['expiration_date' => now()->subDay()->toDateString()])->licenseState());
        $this->assertSame('unknown', Driver::factory()->make(['expiration_date' => null])->licenseState());
    }

    public function test_the_drivers_page_carries_the_licence_state_and_vehicle_lock()
    {
        $admin = $this->userWithRole('admin');
        $driver = Driver::factory()->approved()->create(['expiration_date' => now()->subDays(2)->toDateString()]);
        Vehicle::factory()->withDriver($driver->id)->create(['status' => 'active']);

        $response = $this->actingAs($admin)->get(route('drivers.index'))->assertOk();
        $row = $response->viewData('drivers')->firstWhere('id', $driver->id);

        $this->assertSame('expired', $row->licenseState());
        $this->assertSame(1, (int) $row->vehicles_count);
        $response->assertSee('License Expired');
    }

    // ───────────────── 18. no revocation flag on violation codes ─────────────────

    public function test_a_violation_code_is_created_without_a_revocation_flag()
    {
        $manager = $this->userWithRole('driver_manager');

        $this->actingAs($manager)->post(route('violation-codes.store'), [
            'code' => 'RV-1', 'name' => 'No revocation', 'first' => 100, 'second' => 200,
            'third' => 300, 'fourth_plus' => 400,
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('violation_codes', ['code' => 'RV-1', 'fourth_offense' => 400]);
        $this->assertFalse(\Schema::hasColumn('violation_codes', 'is_revoked'));
    }

    // ───────────────── 19 + 20. violation logs ─────────────────

    protected function loggedDriver(): User
    {
        [$user] = $this->driverUser(['is_approved' => 1, 'is_rejected' => 0]);

        return $user;
    }

    protected function violationRow(ViolationCode $code, array $overrides = []): array
    {
        return array_merge([
            'vc_id' => $code->id, 'violation_instance' => '1', 'violation_fine' => 100,
            'place_of_violation' => 'Colon St', 'date_of_violation' => now()->toDateString(),
            'time_of_violation' => '09:30',
        ], $overrides);
    }

    public function test_bulk_add_stores_every_row_even_without_remarks()
    {
        $manager = $this->userWithRole('driver_manager');
        $driver = $this->loggedDriver();
        $code = ViolationCode::factory()->create();

        $this->actingAs($manager)->post(route('driver-manager.violations-log.store-bulk'), [
            'user_id' => $driver->id,
            'violations' => [
                $this->violationRow($code, ['remarks' => null]),
                $this->violationRow($code, ['violation_instance' => '2', 'remarks' => 'Repeat']),
                $this->violationRow($code, ['violation_instance' => '3']),
            ],
        ])->assertSessionHas('success');

        $this->assertSame(3, ViolationLog::where('user_id', $driver->id)->count());
    }

    public function test_bulk_add_reports_errors_per_row_and_stores_nothing()
    {
        $manager = $this->userWithRole('driver_manager');
        $driver = $this->loggedDriver();
        $code = ViolationCode::factory()->create();

        $this->actingAs($manager)->postJson(route('driver-manager.violations-log.store-bulk'), [
            'user_id' => $driver->id,
            'violations' => [
                $this->violationRow($code),
                $this->violationRow($code, ['place_of_violation' => '', 'vc_id' => '']),
            ],
        ])->assertStatus(422)->assertJsonValidationErrors([
            'violations.1.place_of_violation', 'violations.1.vc_id',
        ]);

        $this->assertSame(0, ViolationLog::where('user_id', $driver->id)->count());
    }

    public function test_a_single_violation_does_not_require_remarks()
    {
        $manager = $this->userWithRole('driver_manager');
        $driver = $this->loggedDriver();
        $code = ViolationCode::factory()->create();

        $this->actingAs($manager)->post(route('driver-manager.violations-log.store'), array_merge(
            $this->violationRow($code),
            ['user_id' => $driver->id]
        ))->assertSessionHas('success');

        $this->assertSame(1, ViolationLog::where('user_id', $driver->id)->count());
    }

    // ───────────── 19/21/26/27. ajax form protocol ─────────────

    public function test_an_ajax_post_that_succeeds_answers_json_with_the_flash_message()
    {
        $manager = $this->userWithRole('driver_manager');

        $this->actingAs($manager)
            ->withHeader('Referer', route('driver-manager.violation-codes'))
            ->postJson(route('violation-codes.store'), [
                'code' => 'AJ-1', 'name' => 'Ajax made', 'first' => 1, 'second' => 2, 'third' => 3, 'fourth_plus' => 4,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Violation code successfully added!')
            ->assertJsonPath('redirect', route('driver-manager.violation-codes'));
    }

    public function test_an_ajax_post_that_fails_validation_keeps_the_field_errors()
    {
        $manager = $this->userWithRole('driver_manager');

        $this->actingAs($manager)
            ->postJson(route('violation-codes.store'), ['code' => '', 'name' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code', 'name', 'first']);
    }

    public function test_an_ajax_post_refused_by_the_application_answers_409_with_the_reason()
    {
        $admin = $this->userWithRole('admin');
        [, $driver] = $this->driverUser();
        Vehicle::factory()->withDriver($driver->id)->create(['status' => 'active']);

        $this->actingAs($admin)
            ->deleteJson(route('drivers.destroy', ['driver' => $driver->id]))
            ->assertStatus(409)
            ->assertJsonPath('type', 'error');
    }

    public function test_the_toast_component_is_mounted_on_pages_and_the_old_flash_view_is_gone()
    {
        $this->assertFileDoesNotExist(resource_path('views/components/flash.blade.php'));

        $this->get(route('login'))->assertOk()->assertSee('window.Toast', false);

        $this->withSession(['success' => 'All good'])
            ->get(route('login'))
            ->assertSee('All good');
    }

    // ───────────────────── 4. replayed payments ─────────────────────

    public function test_a_replayed_pending_payment_goes_back_to_its_status_page_not_to_a_second_checkout()
    {
        $commuter = $this->userWithRole('commuter');
        Wallet::firstOrCreate(['user_id' => $commuter->id]);
        $payment = $this->payment($commuter, 'pending', ['transaction_id' => '#SC-REPLAY01']);

        $this->actingAs($commuter)
            ->post(route('payment.process'), [
                'pickup' => $payment->starting_point, 'destination' => $payment->destination,
                'distance' => 2, 'amount' => 20, 'payment-method' => 'GCash',
                'transaction-id' => '#SC-REPLAY01',
            ])
            ->assertRedirect(route('payment.returned', ['ref' => '#SC-REPLAY01']))
            ->assertSessionHas('warning');

        $this->assertSame(1, Payment::where('transaction_id', '#SC-REPLAY01')->count());
    }

    public function test_the_checkout_page_warns_about_a_payment_still_in_progress_for_the_same_trip()
    {
        $commuter = $this->userWithRole('commuter');
        Wallet::firstOrCreate(['user_id' => $commuter->id]);
        $payment = $this->payment($commuter, 'pending', ['starting_point' => 'A Street', 'destination' => 'B Street']);

        $this->actingAs($commuter)
            ->get(route('payment.index', [
                'pickup' => 'A Street', 'destination' => 'B Street', 'distance' => 3, 'price-regular' => 20,
            ]))
            ->assertOk()
            ->assertSee('already in progress')
            ->assertSee(urlencode($payment->transaction_id), false);
    }

    // ───────────────────── 3. receipt page ─────────────────────

    public function test_the_receipt_page_does_not_pin_the_page_to_a_fixed_height()
    {
        $commuter = $this->userWithRole('commuter');
        $payment = $this->payment($commuter, 'paid');

        $html = $this->actingAs($commuter)->get(route('payment.showReceipt', $payment->id))->assertOk()->getContent();

        $this->assertStringContainsString('Back to History', $html);
        $this->assertStringNotContainsString('items-center justify-center p-3', $html, 'a clipped, vertically-centred column hid the back link');
        $this->assertStringContainsString('min-height: 100%', $html);
    }

    // ───────────────────── 24. maintenance log ─────────────────────

    public function test_the_vehicle_maintenance_log_has_its_own_maintenance_manager_page()
    {
        $this->assertFileExists(app_path('Models/VehicleMaintenanceLog.php'));
        $this->assertTrue(\Schema::hasTable('vehicle_maintenance_logs'));
        $this->assertTrue(Route::has('maintenance-manager.maintenance-logs'));
        $this->assertFalse(Route::has('maintenance-manager.vehicle-maintenance-log'));
    }

    public function test_logging_a_preventive_service_feeds_the_fleet_summary()
    {
        $manager = $this->userWithRole('maintenance_manager');
        $vehicle = Vehicle::factory()->create(['status' => 'active']);
        $task = \App\Models\MaintenanceTask::create(['tasks_performed' => 'Oil Change', 'miles_between_service' => 5000, 'months_between_service' => 6]);

        $this->actingAs($manager)->post(route('maintenance-manager.preventive-maintenance.store'), [
            'vehicle_id' => $vehicle->id, 'task_id' => $task->id, 'last_service_odo' => 12000,
            'last_service_date' => now()->toDateString(), 'last_service_cost' => 1500,
        ])->assertSessionHas('success');

        $summary = app(\App\Services\FleetMaintenanceService::class)->summaryFor($vehicle);

        $this->assertEquals(1500.0, $summary['ytdTotal']);
        $this->assertSame('Oil Change', $summary['allLogs']->first()['task_name']);
        $this->assertSame(12000, (int) $summary['allLogs']->first()['mileage']);

        $this->actingAs($manager)->get(route('dashboard', ['vehicle_id' => $vehicle->id]))->assertOk();
    }

    public function test_a_vehicle_with_preventive_maintenance_is_still_protected_from_deletion()
    {
        $manager = $this->userWithRole('maintenance_manager');
        $vehicle = Vehicle::factory()->create(['status' => 'active']);
        $task = \App\Models\MaintenanceTask::create(['tasks_performed' => 'Brakes', 'miles_between_service' => 5000, 'months_between_service' => 6]);
        \App\Models\PreventiveMaintenance::create([
            'vehicle_id' => $vehicle->id, 'task_id' => $task->id, 'last_service_odo' => 1,
            'last_service_date' => now()->toDateString(), 'last_service_cost' => 1,
        ]);

        $this->actingAs($manager)->deleteJson(route('vehicles.destroy', $vehicle))->assertStatus(409);
    }
}
