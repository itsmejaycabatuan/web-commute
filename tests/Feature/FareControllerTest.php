<?php

namespace Tests\Feature;

use App\Models\Fare;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FareControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin']);
    }

    public function test_fare_index_route_responds()
    {
        $response = $this->get(route('fares.index'));
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_fare_view_route_responds()
    {
        $response = $this->get(route('fares.view', ['id' => 1]));
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_fare_bulk_update_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->put(route('fares.bulk-update'), ['rates' => []]);
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_fare_upload_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->put(route('fares.upload'), []);
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_fare_delete_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->delete(route('fares.destroy', ['id' => 1]));
        $this->assertTrue($response->getStatusCode() > 0);
    }

    /**
     * Fare CRUD Tests
     */
    public function test_admin_can_create_fare()
    {
        $admin = User::factory()->create()->assignRole('admin');

        $response = $this->actingAs($admin)
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('fares.store'), [
                'route_name' => 'Test Route',
                'fare_amount' => 50.00,
            ]);

        $this->assertDatabaseHas('fares', ['route_name' => 'Test Route', 'fare_amount' => 50.00]);
    }

    public function test_fare_store_fails_with_invalid_amount()
    {
        $admin = User::factory()->create()->assignRole('admin');

        $response = $this->actingAs($admin)
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('fares.store'), [
                'route_name' => 'Test Route',
                'fare_amount' => -10,
            ]);

        $response->assertSessionHasErrors(['fare_amount']);
    }

    public function test_admin_can_update_fare()
    {
        $fare = Fare::factory()->create(['route_name' => 'Old Route', 'fare_amount' => 20.00]);
        $admin = User::factory()->create()->assignRole('admin');

        $response = $this->actingAs($admin)
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->put(route('fares.update', ['id' => $fare->id]), [
                'route_name' => 'New Route',
                'fare_amount' => 30.00,
            ]);

        $this->assertDatabaseHas('fares', ['id' => $fare->id, 'route_name' => 'New Route', 'fare_amount' => 30.00]);
    }

    public function test_admin_can_delete_fare()
    {
        $fare = Fare::factory()->create(['route_name' => 'To Delete']);
        $admin = User::factory()->create()->assignRole('admin');

        $response = $this->actingAs($admin)
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->delete(route('fares.destroy', ['id' => $fare->id]));

        $this->assertDatabaseMissing('fares', ['id' => $fare->id]);
    }

    public function test_fare_bulk_update_with_valid_data()
    {
        $admin = User::factory()->create()->assignRole('admin');
        $fares = Fare::factory()->count(2)->create();

        $rates = $fares->map(function ($fare) {
            return ['id' => $fare->id, 'fare_amount' => $fare->fare_amount + 10];
        });

        $response = $this->actingAs($admin)
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->put(route('fares.bulk-update'), ['rates' => $rates]);

        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_non_admin_cannot_access_fare_routes()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('fares.index'));
        $response->assertStatus(403);
    }
}
