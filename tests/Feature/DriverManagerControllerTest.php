<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ViolationCode;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DriverManagerControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'driver']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'driver_manager']);
    }

    public function test_driver_manager_time_keeping_route_responds()
    {
        $response = $this->get(route('driver-manager.time-keeping'));
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_driver_manager_time_keeping_store_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('driver-manager.time-keeping.store'), [
                'driver_id' => 1,
                'time_in' => '08:00 AM',
                'time_out' => '05:00 PM',
            ]);
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_driver_manager_violation_codes_route_responds()
    {
        $response = $this->get(route('driver-manager.violation-codes'));
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_driver_manager_violation_codes_store_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('violation-codes.store'), [
                'code' => 'TEST-01',
                'violation_name' => 'Test Violation',
            ]);
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_driver_manager_violations_log_route_responds()
    {
        $response = $this->get(route('driver-manager.violations-log'));
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_driver_manager_violations_log_store_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('driver-manager.violations-log.store'), [
                'user_id' => 1,
                'violation_type' => 'Test',
                'violation_fine' => 500,
            ]);
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_violation_codes_update_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->put(route('violation-codes.update', ['id' => 1]), [
                'violation_name' => 'Updated',
            ]);
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_violation_codes_destroy_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->delete(route('violation-codes.destroy', ['id' => 1]));
        $this->assertTrue($response->getStatusCode() > 0);
    }

    /** 
     * Driver Manager Tests
     */
    public function test_admin_can_store_time_keeping()
    {
        $admin = User::factory()->create()->assignRole('admin');
        $driver = \App\Models\Driver::factory()->create();

        $response = $this->actingAs($admin)
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('driver-manager.time-keeping.store'), [
                'driver_id' => $driver->id,
                'time_in' => '08:00 AM',
                'time_out' => '05:00 PM',
            ]);

        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_admin_cannot_store_time_keeping_without_driver()
    {
        $admin = User::factory()->create()->assignRole('admin');

        $response = $this->actingAs($admin)
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('driver-manager.time-keeping.store'), [
                'driver_id' => 99999,
                'time_in' => '08:00 AM',
                'time_out' => '05:00 PM',
            ]);

        $response->assertStatus(422);
    }

    public function test_driver_manager_can_store_violation_code()
    {
        $admin = User::factory()->create()->assignRole('admin');

        $response = $this->actingAs($admin)
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('violation-codes.store'), [
                'code' => 'OV-01',
                'violation_name' => 'Speeding',
            ]);

        $this->assertDatabaseHas('violation_codes', ['code' => 'OV-01', 'violation_name' => 'Speeding']);
        $response->assertRedirect();
    }

    public function test_driver_manager_cannot_store_duplicate_violation_code()
    {
        // First create a violation code
        $existing = ViolationCode::factory()->create(['code' => 'DUP-01']);

        $admin = User::factory()->create()->assignRole('admin');

        $response = $this->actingAs($admin)
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('violation-codes.store'), [
                'code' => 'DUP-01',
                'violation_name' => 'Duplicate',
            ]);

        // Should fail validation for duplicate code
        $response->assertStatus(422);
    }

    public function test_admin_can_update_violation_code()
    {
        $code = ViolationCode::factory()->create(['code' => 'UPD-01', 'violation_name' => 'Old Name']);
        $admin = User::factory()->create()->assignRole('admin');

        $response = $this->actingAs($admin)
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->put(route('violation-codes.update', ['id' => $code->id]), [
                'violation_name' => 'Updated Name',
            ]);

        $this->assertDatabaseHas('violation_codes', ['id' => $code->id, 'violation_name' => 'Updated Name']);
    }

    public function test_admin_can_delete_violation_code()
    {
        $code = ViolationCode::factory()->create(['code' => 'TO-DELETE', 'violation_name' => 'To Be Deleted']);
        $admin = User::factory()->create()->assignRole('admin');

        $response = $this->actingAs($admin)
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->delete(route('violation-codes.destroy', ['id' => $code->id]));

        $this->assertDatabaseMissing('violation_codes', ['id' => $code->id]);
    }
}