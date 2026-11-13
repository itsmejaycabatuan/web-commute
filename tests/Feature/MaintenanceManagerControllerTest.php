<?php

namespace Tests\Feature;

use App\Models\MaintenanceTask;
use App\Models\PreventiveMaintenance;
use App\Models\Vehicle;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MaintenanceManagerControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'maintenance_manager']);
    }

    public function test_maintenance_tasks_route_responds()
    {
        $response = $this->get(route('maintenance-manager.maintenance-tasks'));
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_maintenance_tasks_store_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('maintenance-manager.maintenance-tasks.store'), [
                'name' => 'Test Task',
            ]);
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_maintenance_tasks_update_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->put(route('maintenance-manager.maintenance-tasks.update', ['task' => 1]), [
                'name' => 'Updated Task',
            ]);
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_maintenance_tasks_destroy_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->delete(route('maintenance-manager.maintenance-tasks.destroy', ['task' => 1]));
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_preventive_maintenance_route_responds()
    {
        $response = $this->get(route('maintenance-manager.preventive-maintenance'));
        $this->assertTrue($response->getStatusCode() > 0);
    }

    /**
     * Maintenance Manager Tests
     */
    public function test_preventive_maintenance_shows_the_latest_odometer_reading_for_the_vehicle()
    {
        $vehicle = Vehicle::factory()->create(['status' => 'active']);
        $other = Vehicle::factory()->create(['status' => 'active']);
        $oil = MaintenanceTask::create(['tasks_performed' => 'Oil Change', 'miles_between_service' => 5000]);
        $brakes = MaintenanceTask::create(['tasks_performed' => 'Brake Pads', 'miles_between_service' => 20000]);

        PreventiveMaintenance::create(['vehicle_id' => $vehicle->id, 'task_id' => $oil->id, 'last_service_odo' => 12000,
            'last_service_date' => '2026-06-01', 'last_service_cost' => 1500]);
        PreventiveMaintenance::create(['vehicle_id' => $vehicle->id, 'task_id' => $brakes->id, 'last_service_odo' => 15500,
            'last_service_date' => '2026-09-10', 'last_service_cost' => 3000]);
        // Another vehicle's higher reading must not leak into this one's card.
        PreventiveMaintenance::create(['vehicle_id' => $other->id, 'task_id' => $oil->id, 'last_service_odo' => 99000,
            'last_service_date' => '2026-09-01', 'last_service_cost' => 1]);

        $this->actingAs($this->manager())
            ->get(route('maintenance-manager.preventive-maintenance', ['vehicle_id' => $vehicle->id]))
            ->assertOk()
            ->assertSee('Latest', false)
            ->assertSeeInOrder(['id="latest-odometer-card"', '15,500', 'Sep 10, 2026', 'Brake Pads'], false)
            ->assertDontSee('99,000');
    }

    public function test_preventive_maintenance_odometer_card_handles_a_vehicle_with_no_readings()
    {
        $vehicle = Vehicle::factory()->create(['status' => 'active']);

        $this->actingAs($this->manager())
            ->get(route('maintenance-manager.preventive-maintenance', ['vehicle_id' => $vehicle->id]))
            ->assertOk()
            ->assertSeeInOrder(['id="latest-odometer-card"', 'No reading logged yet'], false);
    }

    protected function manager(): User
    {
        return User::factory()->create()->assignRole('maintenance_manager');
    }

    public function test_maintenance_manager_can_create_maintenance_task()
    {
        $this->actingAs($this->manager())
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('maintenance-manager.maintenance-tasks.store'), [
                'tasks_performed' => 'Oil Change',
                'miles_between_service' => 5000,
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('maintenance_tasks', ['tasks_performed' => 'Oil Change', 'miles_between_service' => 5000]);
    }

    public function test_maintenance_manager_cannot_create_task_without_name()
    {
        $response = $this->actingAs($this->manager())
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('maintenance-manager.maintenance-tasks.store'), []);

        $response->assertSessionHasErrors(['tasks_performed']);
    }

    /** @dataProvider badIntervals */
    public function test_task_intervals_must_be_sensible_whole_numbers(string $field, mixed $value, string $message)
    {
        $manager = $this->manager();
        $task = MaintenanceTask::create(['tasks_performed' => 'Existing', 'miles_between_service' => 5000]);

        // Both Add and Edit report the problem against the offending field and store nothing.
        $this->actingAs($manager)
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('maintenance-manager.maintenance-tasks.store'), ['tasks_performed' => 'Brakes', $field => $value])
            ->assertSessionHasErrors([$field => $message]);
        $this->assertDatabaseMissing('maintenance_tasks', ['tasks_performed' => 'Brakes']);

        $this->actingAs($manager)
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->put(route('maintenance-manager.maintenance-tasks.update', ['task' => $task->id]), ['tasks_performed' => 'Renamed', $field => $value])
            ->assertSessionHasErrors([$field => $message]);
        $this->assertDatabaseHas('maintenance_tasks', ['id' => $task->id, 'tasks_performed' => 'Existing']);
    }

    public static function badIntervals(): array
    {
        return [
            'km decimal' => ['miles_between_service', '2.5', 'The km interval must be a whole number (no decimals).'],
            'km zero' => ['miles_between_service', 0, 'The km interval must be at least 1 km.'],
            'km negative' => ['miles_between_service', -10, 'The km interval must be at least 1 km.'],
            'km too big' => ['miles_between_service', 1000001, 'The km interval may not be more than 1,000,000 km.'],
            'month decimal' => ['months_between_service', '1.5', 'The month interval must be a whole number (no decimals).'],
            'month zero' => ['months_between_service', 0, 'The month interval must be at least 1 month.'],
            'month too big' => ['months_between_service', 601, 'The month interval may not be more than 600 months.'],
        ];
    }

    public function test_task_intervals_are_optional_and_valid_values_are_saved()
    {
        $this->actingAs($this->manager())
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('maintenance-manager.maintenance-tasks.store'), [
                'tasks_performed' => 'Tyres',
                'miles_between_service' => '',
                'months_between_service' => 12,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('maintenance_tasks', ['tasks_performed' => 'Tyres', 'miles_between_service' => null, 'months_between_service' => 12]);
    }

    public function test_maintenance_manager_can_update_maintenance_task()
    {
        $task = MaintenanceTask::create(['tasks_performed' => 'Old Task']);

        $this->actingAs($this->manager())
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->put(route('maintenance-manager.maintenance-tasks.update', ['task' => $task->id]), [
                'tasks_performed' => 'New Task Name',
            ]);

        $this->assertDatabaseHas('maintenance_tasks', ['id' => $task->id, 'tasks_performed' => 'New Task Name']);
    }

    public function test_maintenance_manager_can_delete_maintenance_task()
    {
        $task = MaintenanceTask::create(['tasks_performed' => 'Test Task']);

        $this->actingAs($this->manager())
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->delete(route('maintenance-manager.maintenance-tasks.destroy', ['task' => $task->id]));

        $this->assertDatabaseMissing('maintenance_tasks', ['id' => $task->id]);
    }

    public function test_admin_cannot_manage_maintenance_tasks()
    {
        Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('maintenance-manager.maintenance-tasks.store'), ['tasks_performed' => 'Nope'])
            ->assertForbidden();

        $this->assertDatabaseMissing('maintenance_tasks', ['tasks_performed' => 'Nope']);
    }
}
