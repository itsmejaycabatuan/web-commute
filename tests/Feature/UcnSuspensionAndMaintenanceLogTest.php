<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\MaintenanceTask;
use App\Models\PreventiveMaintenance;
use App\Models\TimeKeeping;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleLocation;
use App\Models\VehicleMaintenanceLog;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Coverage for the driver suspension flow (UCN_SC_E012) and for the service-log
 * schema that backs the maintenance history, fleet cost-per-kilometre and
 * report figures.
 */
class UcnSuspensionAndMaintenanceLogTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'driver_manager', 'maintenance_manager', 'driver'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
    }

    protected function admin(): User
    {
        return User::factory()->create()->assignRole('admin');
    }

    protected function maintenanceManager(): User
    {
        return User::factory()->create()->assignRole('maintenance_manager');
    }

    /** A driver user plus their driver profile. */
    protected function driverWithProfile(array $attributes = []): array
    {
        $user = User::factory()->create($attributes)->assignRole('driver');
        $user->markEmailAsVerified();

        $driver = Driver::factory()->create(array_merge(['user_id' => $user->id], $attributes['driver'] ?? []));

        return [$user, $driver];
    }

    // ───────────────────────── driver suspension ─────────────────────────

    public function test_a_driver_can_be_suspended()
    {
        [$user, $driver] = $this->driverWithProfile();

        $this->actingAs($this->admin())
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->patch(route('drivers.suspension', ['driver' => $driver->id]), [
                'suspension_reason' => 'Licence under investigation',
            ])
            ->assertSessionHas('success');

        $driver->refresh();

        $this->assertTrue($driver->is_suspended);
        $this->assertTrue($driver->isSuspended());
        $this->assertNotNull($driver->suspended_at);
        $this->assertSame('Licence under investigation', $driver->suspension_reason);
        $this->assertSame('suspended', $driver->status);
        $this->assertTrue($user->fresh()->isSuspended());
    }

    public function test_a_suspended_driver_cannot_sign_in()
    {
        [$user] = $this->driverWithProfile([
            'email' => 'suspended-driver@example.com',
            'password' => bcrypt('password123'),
        ]);

        $user->driver->suspend();

        auth()->logout();

        $this->post(route('users.login'), [
            'email' => 'suspended-driver@example.com',
            'password' => 'password123',
        ])->assertSessionHasErrors('credentials');

        $this->assertGuest();
    }

    public function test_a_suspended_driver_cannot_clock_in()
    {
        [$user, $driver] = $this->driverWithProfile();

        $driver->suspend();

        $before = TimeKeeping::count();

        $this->actingAs($user)
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('driver.timekeeping.clock-in'))
            ->assertSessionHas('error');

        $this->assertSame($before, TimeKeeping::count());
    }

    public function test_a_suspended_driver_cannot_broadcast_a_location()
    {
        [$user, $driver] = $this->driverWithProfile();
        $vehicle = Vehicle::factory()->create(['driver_id' => $driver->id]);

        $driver->suspend();

        $this->actingAs($user)
            ->postJson(route('vehicle.broadcast'), [
                'vehicle_id' => (string) $vehicle->id,
                'latitude' => 14.5995,
                'longitude' => 120.9842,
                'user_id' => $user->id,
            ])
            ->assertStatus(403);

        $this->assertSame(0, VehicleLocation::where('vehicle_id', $vehicle->id)->count());
    }

    public function test_suspending_a_driver_detaches_their_vehicle()
    {
        [, $driver] = $this->driverWithProfile();
        $vehicle = Vehicle::factory()->create(['driver_id' => $driver->id]);

        $driver->suspend();

        $this->assertNull($vehicle->fresh()->driver_id);
    }

    public function test_a_suspended_driver_can_be_reactivated()
    {
        [, $driver] = $this->driverWithProfile();
        $driver->suspend();

        $this->actingAs($this->admin())
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->patch(route('drivers.suspension', ['driver' => $driver->id]))
            ->assertSessionHas('success');

        $driver->refresh();

        $this->assertFalse($driver->is_suspended);
        $this->assertNull($driver->suspended_at);
        $this->assertSame('inactive', $driver->status);
    }

    public function test_a_driver_holding_a_vehicle_cannot_be_deleted()
    {
        [, $driver] = $this->driverWithProfile();
        Vehicle::factory()->create(['driver_id' => $driver->id]);

        $this->actingAs($this->admin())
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->delete(route('drivers.destroy', ['driver' => $driver->id]))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('drivers', ['id' => $driver->id]);
    }

    public function test_the_drivers_page_shows_the_suspension_state()
    {
        [, $driver] = $this->driverWithProfile();
        $driver->suspend();

        $response = $this->actingAs($this->admin())->get(route('drivers.index'));

        $response->assertOk();
        $this->assertTrue($response->viewData('drivers')->first()->is_suspended);
    }

    // ────────────────────── maintenance log schema ──────────────────────

    protected function serviceLogPayload(array $overrides = []): array
    {
        return array_merge([
            'vehicle_id' => null,
            'maintenance_task_id' => null,
            'service_date' => now()->toDateString(),
            'mileage_at_service' => 50500,
            'performed_by' => 'Workshop A',
            'cost' => 3000,
            'invoice_number' => 'INV-1',
            'remarks' => 'Routine service',
        ], $overrides);
    }

    public function test_a_service_log_is_stored_with_its_own_columns()
    {
        $vehicle = Vehicle::factory()->create();
        $task = MaintenanceTask::create([
            'tasks_performed' => 'Oil Change',
            'miles_between_service' => 5000,
            'months_between_service' => 6,
        ]);

        $this->actingAs($this->maintenanceManager())
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('maintenance-manager.vehicle-maintenance-log.store'), $this->serviceLogPayload([
                'vehicle_id' => $vehicle->id,
                'maintenance_task_id' => $task->id,
            ]))
            ->assertSessionHas('success');

        $log = VehicleMaintenanceLog::firstOrFail();

        $this->assertSame($vehicle->id, $log->vehicle_id);
        $this->assertSame($task->id, $log->maintenance_task_id);
        $this->assertSame(50500, $log->mileage_at_service);
        $this->assertSame('Workshop A', $log->performed_by);
        $this->assertEquals(3000.0, (float) $log->cost);
        $this->assertSame('INV-1', $log->invoice_number);
        $this->assertNull($log->maintenance_id);

        // The log relates to its vehicle and task directly.
        $log->load(['vehicle', 'maintenanceTask']);
        $this->assertSame($vehicle->plate_number, $log->vehicle->plate_number);
        $this->assertSame('Oil Change', $log->maintenanceTask->tasks_performed);
    }

    public function test_logging_a_scheduled_service_also_writes_a_service_log()
    {
        $vehicle = Vehicle::factory()->create();
        $task = MaintenanceTask::create([
            'tasks_performed' => 'Brake Inspection',
            'miles_between_service' => 5000,
            'months_between_service' => 6,
        ]);

        $this->actingAs($this->maintenanceManager())
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('maintenance-manager.preventive-maintenance.store'), [
                'vehicle_id' => $vehicle->id,
                'task_id' => $task->id,
                'last_service_odo' => 50000,
                'last_service_date' => now()->subMonth()->toDateString(),
                'last_service_cost' => 2500,
                'comments' => 'Scheduled',
            ])
            ->assertSessionHas('success');

        $log = VehicleMaintenanceLog::firstOrFail();
        $schedule = PreventiveMaintenance::firstOrFail();

        $this->assertSame($schedule->id, $log->maintenance_id);
        $this->assertSame($vehicle->id, $log->vehicle_id);
        $this->assertSame($task->id, $log->maintenance_task_id);
        $this->assertSame(50000, $log->mileage_at_service);
        $this->assertEquals(2500.0, (float) $log->cost);
        $this->assertSame('Scheduled', $log->remarks);
    }

    public function test_a_service_log_can_be_updated_and_deleted()
    {
        $vehicle = Vehicle::factory()->create();
        $task = MaintenanceTask::create([
            'tasks_performed' => 'Tire Rotation',
            'miles_between_service' => 5000,
            'months_between_service' => 6,
        ]);

        $log = VehicleMaintenanceLog::create($this->serviceLogPayload([
            'vehicle_id' => $vehicle->id,
            'maintenance_task_id' => $task->id,
        ]));

        $this->actingAs($this->maintenanceManager())
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->patch(route('maintenance-manager.vehicle-maintenance-log.update', ['log' => $log->id]),
                $this->serviceLogPayload([
                    'vehicle_id' => $vehicle->id,
                    'maintenance_task_id' => $task->id,
                    'cost' => 4200,
                    'performed_by' => 'Workshop B',
                ]))
            ->assertSessionHas('success');

        $log->refresh();
        $this->assertEquals(4200.0, (float) $log->cost);
        $this->assertSame('Workshop B', $log->performed_by);

        $this->actingAs($this->maintenanceManager())
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->delete(route('maintenance-manager.vehicle-maintenance-log.destroy', ['log' => $log->id]))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('vehicle_maintenance_logs', ['id' => $log->id]);
    }

    public function test_the_maintenance_log_page_lists_the_logs_of_the_selected_vehicle()
    {
        $vehicle = Vehicle::factory()->create(['plate_number' => 'LOG-1']);
        $other = Vehicle::factory()->create(['plate_number' => 'LOG-2']);
        $task = MaintenanceTask::create([
            'tasks_performed' => 'Air Filter Change',
            'miles_between_service' => 5000,
            'months_between_service' => 6,
        ]);

        VehicleMaintenanceLog::create($this->serviceLogPayload([
            'vehicle_id' => $vehicle->id,
            'maintenance_task_id' => $task->id,
        ]));
        VehicleMaintenanceLog::create($this->serviceLogPayload([
            'vehicle_id' => $other->id,
            'maintenance_task_id' => $task->id,
            'mileage_at_service' => 1,
        ]));

        $response = $this->actingAs($this->maintenanceManager())
            ->get(route('maintenance-manager.maintenance-logs', ['vehicle' => $vehicle->id]));

        $response->assertOk();

        $logs = $response->viewData('logs');
        $this->assertCount(1, $logs);
        $this->assertSame($vehicle->id, $logs->first()->vehicle_id);
        $this->assertEquals(3000.0, (float) $logs->first()->cost);
    }

    public function test_a_vehicle_with_service_logs_is_preserved()
    {
        $vehicle = Vehicle::factory()->create();
        $task = MaintenanceTask::create([
            'tasks_performed' => 'Oil Change',
            'miles_between_service' => 5000,
            'months_between_service' => 6,
        ]);

        VehicleMaintenanceLog::create($this->serviceLogPayload([
            'vehicle_id' => $vehicle->id,
            'maintenance_task_id' => $task->id,
        ]));

        $this->actingAs($this->maintenanceManager())
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->delete(route('vehicles.destroy', ['vehicle' => $vehicle->id]))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id]);
    }
}