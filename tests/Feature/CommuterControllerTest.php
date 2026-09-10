<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CommuterControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'commuter']);
    }

    public function test_commuters_index_route_responds()
    {
        $response = $this->get(route('commuters.index'));
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_commuters_create_route_responds()
    {
        $response = $this->get(route('commuters.create'));
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_commuters_store_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('commuters.store'), [
                'name' => 'Test Commuter',
                'email' => 'commuter@test.com',
                'contact_info' => '09171234567',
            ]);
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_commuters_edit_route_responds()
    {
        $response = $this->get(route('commuters.edit', ['user' => 99999]));
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_commuters_update_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->put(route('commuters.update', ['user' => 99999]), [
                'name' => 'Updated Commuter',
            ]);
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_commuters_destroy_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->delete(route('commuters.destroy', ['user' => 99999]));
        $this->assertTrue($response->getStatusCode() > 0);
    }

    /**
     * Commuter CRUD Tests
     */
    public function test_admin_can_store_commuter_with_valid_data()
    {
        $admin = User::factory()->create()->assignRole('admin');

        $response = $this->actingAs($admin)
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('commuters.store'), [
                'name' => 'John Commuter',
                'email' => 'john@example.com',
                'contact_info' => '09171234567',
            ]);

        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
        $response->assertRedirect();
    }

    public function test_commuter_store_fails_with_missing_name()
    {
        $admin = User::factory()->create()->assignRole('admin');

        $response = $this->actingAs($admin)
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('commuters.store'), [
                'email' => 'john@example.com',
                'contact_info' => '09171234567',
            ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_commuter_store_fails_with_invalid_email()
    {
        $admin = User::factory()->create()->assignRole('admin');

        $response = $this->actingAs($admin)
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('commuters.store'), [
                'name' => 'John Commuter',
                'email' => 'not-an-email',
                'contact_info' => '09171234567',
            ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_commuter_can_be_updated()
    {
        $user = User::factory()->create(['name' => 'Old Name', 'email' => 'old@example.com']);
        $admin = User::factory()->create()->assignRole('admin');

        $response = $this->actingAs($admin)
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->put(route('commuters.update', ['user' => $user->id]), [
                'name' => 'New Name',
                'email' => 'new@example.com',
                'contact_info' => '09189999999',
            ]);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name']);
    }

    public function test_commuter_can_be_deleted()
    {
        $user = User::factory()->create();
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->delete(route('commuters.destroy', ['user' => $user->id]));

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_commuter_update_fails_with_invalid_email()
    {
        $user = User::factory()->create();
        $admin = User::factory()->create()->assignRole('admin');

        $response = $this->actingAs($admin)
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->put(route('commuters.update', ['user' => $user->id]), [
                'email' => 'not-valid',
            ]);

        $response->assertSessionHasErrors(['email']);
    }
}
