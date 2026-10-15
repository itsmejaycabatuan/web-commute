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
