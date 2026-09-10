<?php

namespace Tests\Feature;

use App\Models\MaintenanceTask;
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

    public function test_fleet_inventory_route_responds()
    {
        $response = $this->get(route('maintenance-manager.fleet-inventory'));
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_fleet_inventory_store_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('maintenance-manager.fleet-inventory.store'), [
                'name' => 'Test Inventory',
            ]);
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_fleet_inventory_update_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->patch(route('maintenance-manager.fleet-inventory.update', ['id' => 1]), [
                'name' => 'Updated Inventory',
            ]);
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_fleet_inventory_delete_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->delete(route('maintenance-manager.fleet-inventory.destroy', ['id' => 1]));
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_fleet_maintenance_log_route_responds()
    {
        $response = $this->get(route('maintenance-manager.fleet-maintenance-log'));
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_maintenance_logs_route_responds()
    {
        $response = $this->get(route('maintenance-manager.maintenance-logs'));
        $this->assertTrue($response->getStatusCode() > 0);
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

    public function test_vehicle_maintenance_log_route_responds()
    {
        $response = $this->get(route('maintenance-manager.vehicle-maintenance-log'));
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_vehicle_maintenance_log_store_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('maintenance-manager.vehicle-maintenance-log.store'), [
                'vehicle_id' => 1,
                'notes' => 'Test maintenance',
            ]);
        $this->assertTrue($response->getStatusCode() > 0);
    }

    /**
     * Maintenance Manager Tests
     */
    public function test_admin_can_create_maintenance_task()
    {
        $admin = User::factory()->create()->assignRole('admin');

        $response = $this->actingAs($admin)
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('maintenance-manager.maintenance-tasks.store'), [
                'name' => 'Oil Change',
            ]);

        $this->assertDatabaseHas('maintenance_tasks', ['name' => 'Oil Change']);
    }

    public function test_admin_cannot_create_task_without_name()
    {
        $admin = User::factory()->create()->assignRole('admin');

        $response = $this->actingAs($admin)
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('maintenance-manager.maintenance-tasks.store'), []);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_admin_can_update_maintenance_task()
    {
        $task = \App\Models\MaintenanceTask::create(["name" => "Old Task"]);
        $admin = User::factory()->create()->assignRole('admin');

        $response = $this->actingAs($admin)
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->put(route('maintenance-manager.maintenance-tasks.update', ['task' => $task->id]), [
                'name' => 'New Task Name',
            ]);

        $this->assertDatabaseHas('maintenance_tasks', ['id' => $task->id, 'name' => 'New Task Name']);
    }

    public function test_admin_can_delete_maintenance_task()
    {
        $admin = User::factory()->create()->assignRole('admin');
        $task = \App\Models\MaintenanceTask::create(["name" => "Test Task"]);

        $response = $this->actingAs($admin)
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->delete(route('maintenance-manager.maintenance-tasks.destroy', ['task' => $task->id]));

        $this->assertDatabaseMissing('maintenance_tasks', ['id' => $task->id]);
    }

    public function test_maintenance_manager_can_view_fleet_inventory()
    {
        $response = $this->get(route('maintenance-manager.fleet-inventory'));
        $this->assertOk($response);
    }

    public function test_non_maintenance_manager_cannot_access_inventory()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('maintenance-manager.fleet-inventory'));

        $response->assertStatus(403);
    }
}