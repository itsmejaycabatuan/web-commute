<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\MaintenanceTask;
use App\Models\PreventiveMaintenance;
use App\Models\TimeKeeping;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleLocation;
use App\Models\ViolationCode;
use App\Models\ViolationLog;
use App\Models\Wallet;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Regression coverage for the staff-facing use cases (UCN_SC_E012 – E020) that
 * were reconciled with the implementation: driver, timekeeping, violation,
 * vehicle, maintenance, commuter, fare-rate and report behaviours.
 */
class UcnStaffRegressionTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'driver_manager', 'maintenance_manager', 'driver', 'commuter'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
    }

    protected function admin(): User
    {
        return User::factory()->create()->assignRole('admin');
    }

    protected function driverManager(): User
    {
        return User::factory()->create()->assignRole('driver_manager');
    }

    protected function maintenanceManager(): User
    {
        return User::factory()->create()->assignRole('maintenance_manager');
    }

    protected function driver(): Driver
    {
        $user = User::factory()->create()->assignRole('driver');
        $user->markEmailAsVerified();

        return Driver::factory()->create(['user_id' => $user->id]);
    }

    // ─────────────────────────── E012 — Manage Drivers ───────────────────────────

    /** E012 E2 — the license number must be unique across drivers. */
    public function test_a_duplicate_license_number_is_rejected()
    {
        Driver::factory()->create(['license_number' => 'LIC-999']);

        $response = $this->actingAs($this->admin())
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('drivers.store'), [
                'driver_code' => 'DC-9001',
                'name' => 'Juan Dela Cruz',
                'email' => 'juan@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'license_number' => 'LIC-999',
                'license_code' => 'AB-1234',
                'expiration_date' => '2030-01-01',
                'contact_info' => '09171234567',
            ]);

        $response->assertSessionHasErrors('license_number');
        $this->assertSame(1, Driver::where('license_number', 'LIC-999')->count());
    }

    // ────────────────────────── E013 — Manage Timekeeping ─────────────────────────

    /** E013 E4 — clock out before clock in is refused. */
    public function test_a_time_entry_where_clock_out_precedes_clock_in_is_rejected()
    {
        $driver = $this->driver();
        $manager = $this->driverManager();

        $response = $this->actingAs($manager)
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('driver-manager.time-keeping.store'), [
                'driver_id' => $driver->id,
                'date' => now()->toDateString(),
                'time_in' => '17:00',
                'time_out' => '09:00',
            ]);

        $response->assertSessionHasErrors('time_out');
        $this->assertSame(0, TimeKeeping::where('driver_id', $driver->id)->count());
    }

    /** E013 E5 — a shift that overlaps an existing entry is refused. */
    public function test_an_overlapping_shift_is_rejected()
    {
        $driver = $this->driver();
        $manager = $this->driverManager();
        $date = now()->toDateString();

        TimeKeeping::factory()->create([
            'driver_id' => $driver->id,
            'date' => $date,
            'time_in' => '08:00',
            'time_out' => '12:00',
            'sick' => 0,
            'vacation' => 0,
        ]);

        $response = $this->actingAs($manager)
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('driver-manager.time-keeping.store'), [
                'driver_id' => $driver->id,
                'date' => $date,
                'time_in' => '11:00',
                'time_out' => '15:00',
            ]);

        $response->assertSessionHasErrors('time_in');
        $this->assertSame(1, TimeKeeping::where('driver_id', $driver->id)->count());
    }

    /** A non-overlapping shift on the same day is still accepted. */
    public function test_a_second_non_overlapping_shift_is_accepted()
    {
        $driver = $this->driver();
        $manager = $this->driverManager();
        $date = now()->toDateString();

        TimeKeeping::factory()->create([
            'driver_id' => $driver->id,
            'date' => $date,
            'time_in' => '08:00',
            'time_out' => '12:00',
            'sick' => 0,
            'vacation' => 0,
        ]);

        $this->actingAs($manager)
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('driver-manager.time-keeping.store'), [
                'driver_id' => $driver->id,
                'date' => $date,
                'time_in' => '13:00',
                'time_out' => '17:00',
            ])
            ->assertSessionHas('success');

        $this->assertSame(2, TimeKeeping::where('driver_id', $driver->id)->count());
    }

    // ────────────────────────── E013 — Manage Violations ──────────────────────────

    /** E013 E2 — a violation code cannot be created twice. */
    public function test_a_duplicate_violation_code_is_rejected_on_create()
    {
        ViolationCode::factory()->create(['code' => 'DUP-01']);
        $manager = $this->driverManager();

        $response = $this->actingAs($manager)
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('violation-codes.store'), [
                'code' => 'DUP-01',
                'name' => 'Duplicate',
                'first' => 100,
                'second' => 200,
                'third' => 300,
                'fourth_plus' => 400,
                'is_revocation' => 0,
            ]);

        $response->assertSessionHasErrors('code');
        $this->assertSame(1, ViolationCode::where('code', 'DUP-01')->count());
    }

    /** E013 E5 — a violation code referenced by a log cannot be deleted. */
    public function test_an_in_use_violation_code_cannot_be_deleted()
    {
        $code = ViolationCode::factory()->create();
        ViolationLog::factory()->create(['vc_id' => $code->id]);

        $this->actingAs($this->driverManager())
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->delete(route('violation-codes.destroy', ['id' => $code->id]))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('violation_codes', ['id' => $code->id]);
    }

    /** An unused violation code is still deletable. */
    public function test_an_unused_violation_code_can_be_deleted()
    {
        $code = ViolationCode::factory()->create();

        $this->actingAs($this->driverManager())
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->delete(route('violation-codes.destroy', ['id' => $code->id]))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('violation_codes', ['id' => $code->id]);
    }

    /** E013 E6 — a violation cannot be dated in the future. */
    public function test_a_future_violation_date_is_rejected()
    {
        $user = User::factory()->create()->assignRole('driver');
        $user->markEmailAsVerified();
        $driver = Driver::factory()->create(['user_id' => $user->id]);
        $code = ViolationCode::factory()->create();

        $response = $this->actingAs($this->driverManager())
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('driver-manager.violations-log.store'), [
                'user_id' => $user->id,
                'vc_id' => $code->id,
                'violation_instance' => 1,
                'violation_fine' => 300,
                'place_of_violation' => 'EDSA',
                'date_of_violation' => now()->addWeek()->toDateString(),
                'time_of_violation' => '08:00',
                'remarks' => 'Test',
            ]);

        $response->assertSessionHasErrors('date_of_violation');
        $this->assertSame(0, ViolationLog::where('vc_id', $code->id)->count());
        unset($driver);
    }

    /** A7 — the log can be narrowed by driver name. */
    public function test_the_violation_log_can_be_searched_by_driver_name()
    {
        $manager = $this->driverManager();

        $wanted = User::factory()->create(['email' => 'wanted@example.com'])->assignRole('driver');
        $wanted->markEmailAsVerified();
        Driver::factory()->create(['user_id' => $wanted->id, 'name' => 'Baldwin Mendoza']);

        $other = User::factory()->create(['email' => 'other@example.com'])->assignRole('driver');
        $other->markEmailAsVerified();
        Driver::factory()->create(['user_id' => $other->id, 'name' => 'Cecilia Ramos']);

        $codes = collect(['SRCH-1', 'SRCH-2'])->map(function ($code, $i) {
            return ViolationCode::create([
                'code' => $code,
                'violation_name' => 'Searchable',
                'first_offense' => 100,
                'second_offense' => 200,
                'third_offense' => 300,
                'fourth_offense' => 400,
                'is_revoked' => 0,
            ]);
        });

        foreach ([$wanted, $other] as $i => $driverUser) {
            ViolationLog::create([
                'user_id' => $driverUser->id,
                'vc_id' => $codes[$i]->id,
                'violation_instance' => '1',
                'violation_fine' => 500,
                'date_of_violation' => now()->toDateString(),
                'time_of_violation' => '08:00:00',
                'place_of_violation' => 'EDSA',
                'remarks' => 'Test violation',
            ]);
        }

        $response = $this->actingAs($manager)
            ->get(route('driver-manager.violations-log', ['search' => 'Baldwin']));

        $response->assertOk();

        // The log table is narrowed to the matching driver, while the driver
        // picker (used by the log modal) still lists everyone.
        $violations = $response->viewData('violations');
        $this->assertCount(1, $violations);
        $this->assertSame('Baldwin Mendoza', $violations->first()['driverName']);
    }

    // ─────────────────────────── E015 — Manage Vehicles ───────────────────────────

    /** E015 E4 — a driver cannot be assigned to a vehicle under maintenance. */
    public function test_a_driver_cannot_be_assigned_to_a_vehicle_under_maintenance()
    {
        $driver = $this->driver();

        $response = $this->actingAs($this->maintenanceManager())
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('vehicles.store'), $this->vehiclePayload([
                'status' => 'maintenance',
                'driver_id' => $driver->id,
            ]));

        $response->assertSessionHasErrors('driver_id');
        $this->assertSame(0, Vehicle::where('plate_number', 'NMA-101')->count());
    }

    /** E015 E3 — a vehicle with a driver currently clocked in cannot be disposed. */
    public function test_a_vehicle_with_a_clocked_in_driver_cannot_be_disposed()
    {
        $driver = $this->driver();
        $vehicle = Vehicle::factory()->create([
            'plate_number' => 'NMA-102',
            'status' => 'active',
            'driver_id' => $driver->id,
        ]);

        TimeKeeping::factory()->create([
            'driver_id' => $driver->id,
            'date' => now()->toDateString(),
            'time_in' => now()->subHour()->format('h:i A'),
            'time_out' => null,
            'sick' => 0,
            'vacation' => 0,
        ]);

        $response = $this->actingAs($this->maintenanceManager())
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->patch(route('vehicles.update', ['vehicle' => $vehicle->id]), $this->vehiclePayload([
                'plate_number' => 'NMA-102',
                'status' => 'disposed',
                'driver_id' => $driver->id,
            ]));

        $response->assertSessionHasErrors('status');
        $this->assertSame('active', $vehicle->fresh()->status);
    }

    /** Postcondition — taking a vehicle out of service detaches its driver. */
    public function test_setting_a_vehicle_to_maintenance_detaches_its_driver()
    {
        $driver = $this->driver();
        $vehicle = Vehicle::factory()->create([
            'plate_number' => 'NMA-103',
            'status' => 'active',
            'driver_id' => $driver->id,
        ]);

        $this->actingAs($this->maintenanceManager())
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->patch(route('vehicles.update', ['vehicle' => $vehicle->id]), $this->vehiclePayload([
                'plate_number' => 'NMA-103',
                'status' => 'maintenance',
                'driver_id' => $driver->id,
            ]))
            ->assertSessionHas('success');

        $this->assertSame('maintenance', $vehicle->fresh()->status);
        $this->assertNull($vehicle->fresh()->driver_id);
    }

    /** Postcondition — a vehicle under maintenance drops off the live map. */
    public function test_a_vehicle_under_maintenance_is_not_shown_as_active()
    {
        $vehicle = Vehicle::factory()->create(['status' => 'maintenance']);

        \App\Models\VehicleLocation::create([
            'vehicle_id' => $vehicle->id,
            'latitude' => 14.5995,
            'longitude' => 120.9842,
            'speed' => 20,
            'accuracy' => 5,
            'last_update' => now(),
        ]);

        $response = $this->actingAs($this->admin())
            ->getJson('/track/vehicles/active')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertCount(0, $response->json('vehicles'));
    }

    /** Vehicle administration is limited to the maintenance manager. */
    public function test_a_commuter_cannot_reach_the_vehicle_management_page()
    {
        $commuter = User::factory()->create()->assignRole('commuter');
        $commuter->markEmailAsVerified();

        $this->actingAs($commuter)->get(route('vehicles.index'))->assertForbidden();
    }

    /** A vehicle with a service history is preserved for auditing. */
    public function test_a_vehicle_with_maintenance_records_cannot_be_deleted()
    {
        $vehicle = Vehicle::factory()->create();
        $task = MaintenanceTask::create([
            'tasks_performed' => 'Brake Inspection',
            'miles_between_service' => 5000,
            'months_between_service' => 6,
        ]);
        PreventiveMaintenance::create([
            'vehicle_id' => $vehicle->id,
            'task_id' => $task->id,
            'last_service_odo' => 1000,
            'last_service_date' => now()->toDateString(),
            'last_service_cost' => 500,
        ]);

        $this->actingAs($this->maintenanceManager())
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->delete(route('vehicles.destroy', ['vehicle' => $vehicle->id]))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id]);
    }

    protected function vehiclePayload(array $overrides = []): array
    {
        return array_merge([
            'year' => 2022,
            'brand' => 'Toyota',
            'model' => 'Hiace',
            'plate_number' => 'NMA-101',
            'vin' => 'JTDBR11E301234567',
            'fuel_type' => 'Diesel',
            'tank_capacity' => '70',
            'driver_id' => null,
            'location' => 'Cubao',
            'status' => 'active',
            'acquisition_date' => '2022-01-01',
            'exp_disposal_date' => '2032-01-01',
        ], $overrides);
    }

    // ─────────────────────── E015 — Maintenance Schedule ───────────────────────

    /** E015 E2 — an odometer reading below the last recorded one is rejected. */
    public function test_an_odometer_reading_below_the_last_recorded_one_is_rejected()
    {
        $vehicle = Vehicle::factory()->create();
        $task = MaintenanceTask::create([
            'tasks_performed' => 'Oil Change',
            'miles_between_service' => 5000,
            'months_between_service' => 6,
        ]);

        PreventiveMaintenance::create([
            'vehicle_id' => $vehicle->id,
            'task_id' => $task->id,
            'last_service_odo' => 50000,
            'last_service_date' => now()->subMonths(6)->toDateString(),
            'last_service_cost' => 2500,
        ]);

        $response = $this->actingAs($this->maintenanceManager())
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('maintenance-manager.preventive-maintenance.store'), [
                'vehicle_id' => $vehicle->id,
                'task_id' => $task->id,
                'last_service_odo' => 40000,
                'last_service_date' => now()->toDateString(),
                'last_service_cost' => 2500,
            ]);

        $response->assertSessionHasErrors('last_service_odo');
        $this->assertSame(50000, (int) PreventiveMaintenance::where('vehicle_id', $vehicle->id)->value('last_service_odo'));
    }

    /** A reading at or above the last recorded one is accepted. */
    public function test_an_odometer_reading_above_the_last_recorded_one_is_accepted()
    {
        $vehicle = Vehicle::factory()->create();
        $task = MaintenanceTask::create([
            'tasks_performed' => 'Brake Inspection',
            'miles_between_service' => 5000,
            'months_between_service' => 6,
        ]);

        PreventiveMaintenance::create([
            'vehicle_id' => $vehicle->id,
            'task_id' => $task->id,
            'last_service_odo' => 50000,
            'last_service_date' => now()->subMonths(6)->toDateString(),
            'last_service_cost' => 2500,
        ]);

        $this->actingAs($this->maintenanceManager())
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('maintenance-manager.preventive-maintenance.store'), [
                'vehicle_id' => $vehicle->id,
                'task_id' => $task->id,
                'last_service_odo' => 50500,
                'last_service_date' => now()->toDateString(),
                'last_service_cost' => 2500,
            ])
            ->assertSessionHas('success');

        $this->assertSame(50500, (int) PreventiveMaintenance::where('vehicle_id', $vehicle->id)->value('last_service_odo'));
    }

    // ─────────────────────────── E018 — Manage Commuters ─────────────────────────

    /** E018 E8 — a commuter holding a balance cannot be deleted. */
    public function test_a_commuter_with_a_balance_cannot_be_deleted()
    {
        $commuter = User::factory()->create()->assignRole('commuter');
        Wallet::factory()->create(['user_id' => $commuter->id, 'balance' => '250.00']);

        $this->actingAs($this->admin())
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->delete(route('commuters.destroy', ['user' => $commuter->id]))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $commuter->id]);
    }

    /** A commuter with an empty wallet can be deleted. */
    public function test_a_commuter_without_a_balance_can_be_deleted()
    {
        $commuter = User::factory()->create()->assignRole('commuter');
        Wallet::factory()->create(['user_id' => $commuter->id, 'balance' => '0.00']);

        $this->actingAs($this->admin())
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->delete(route('commuters.destroy', ['user' => $commuter->id]))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $commuter->id]);
    }

    /** A4 — suspending a commuter locks them out of signing in. */
    public function test_a_suspended_commuter_cannot_sign_in()
    {
        $commuter = User::factory()->create([
            'email' => 'suspended@example.com',
            'password' => bcrypt('password123'),
        ])->assignRole('commuter');
        $commuter->markEmailAsVerified();

        $this->actingAs($this->admin())
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->patch(route('commuters.suspension', ['user' => $commuter->id]))
            ->assertSessionHas('success');

        $this->assertTrue($commuter->fresh()->isSuspended());

        // Sign out of the admin session so the guest-guarded login form is reachable.
        auth()->logout();

        $this->post(route('users.login'), [
            'email' => 'suspended@example.com',
            'password' => 'password123',
        ])->assertSessionHasErrors('credentials');

        $this->assertGuest();
    }

    /** A4 — the suspension is lifted again. */
    public function test_a_suspended_commuter_can_be_reactivated()
    {
        $commuter = User::factory()->create()->assignRole('commuter');
        $commuter->suspend();

        $this->actingAs($this->admin())
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->patch(route('commuters.suspension', ['user' => $commuter->id]))
            ->assertSessionHas('success');

        $this->assertFalse($commuter->fresh()->isSuspended());
    }

    /** A5 — the read-only commuter details page renders. */
    public function test_the_commuter_details_page_renders()
    {
        $commuter = User::factory()->create()->assignRole('commuter');
        Wallet::factory()->create(['user_id' => $commuter->id, 'balance' => '125.50']);

        $this->actingAs($this->admin())
            ->get(route('commuters.show', ['user' => $commuter->id]))
            ->assertOk()
            ->assertSee($commuter->email)
            ->assertSee('125.50');
    }

    // ─────────────────────────── E019 — Manage Fare Rates ─────────────────────────

    /** E019 E4 — a rate of zero or less is refused. */
    public function test_a_zero_or_negative_fare_rate_is_rejected()
    {
        $fare = \App\Models\Fare::factory()->create();
        $rate = \App\Models\FareRate::factory()->create(['fare_id' => $fare->id, 'regular' => 15, 'discount' => 12]);

        $this->actingAs($this->admin())
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->put(route('fares.bulk-update'), [
                'rates' => [
                    $rate->id => ['id' => $rate->id, 'regular' => 0, 'discount' => -5],
                ],
            ])
            ->assertSessionHasErrors();

        $this->assertEquals(15.0, (float) $rate->fresh()->regular);
    }

    /** E019 E2 — an empty rate set is refused. */
    public function test_an_empty_rate_submission_is_rejected()
    {
        $this->actingAs($this->admin())
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->put(route('fares.bulk-update'), [])
            ->assertSessionHasErrors('rates');
    }

    /** A valid rate submission is saved. */
    public function test_a_valid_rate_submission_is_saved()
    {
        $fare = \App\Models\Fare::factory()->create();
        $rate = \App\Models\FareRate::factory()->create(['fare_id' => $fare->id, 'regular' => 15, 'discount' => 12]);

        $this->actingAs($this->admin())
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->put(route('fares.bulk-update'), [
                'rates' => [
                    $rate->id => ['id' => $rate->id, 'regular' => 18, 'discount' => 14],
                ],
            ])
            ->assertSessionHas('success');

        $this->assertEquals(18.0, (float) $rate->fresh()->regular);
    }

    // ────────────────────────── E020 — View System Reports ───────────────────────

    /** E020 E3 — an empty window reports that no data was found. */
    public function test_a_report_over_an_empty_window_reports_no_data()
    {
        $this->actingAs($this->admin())
            ->get(route('reports.generate', [
                'type' => 'financial',
                'start_date' => now()->subYears(3)->toDateString(),
                'end_date' => now()->subYears(3)->addDay()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('No data found');
    }

    /** E020 — an inverted date range is refused. */
    public function test_an_inverted_date_range_is_rejected()
    {
        $this->actingAs($this->admin())
            ->get(route('reports.generate', [
                'type' => 'financial',
                'start_date' => now()->toDateString(),
                'end_date' => now()->subWeek()->toDateString(),
            ]))
            ->assertSessionHasErrors('start_date');
    }
}