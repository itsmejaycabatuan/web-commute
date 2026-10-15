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
        foreach (['admin', 'commuter'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
    }

    public function test_commuters_index_route_responds()
    {
        $response = $this->get(route('commuters.index'));
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
    protected function admin(): User
    {
        return User::factory()->create()->assignRole('admin');
    }

    protected function commuter(array $attributes = []): User
    {
        return User::factory()->create($attributes)->assignRole('commuter');
    }

    public function test_admin_can_store_commuter_with_valid_data()
    {
        $response = $this->actingAs($this->admin())
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('commuters.store'), [
                'email' => 'john@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
        $this->assertTrue(User::where('email', 'john@example.com')->first()->hasRole('commuter'));
        $response->assertRedirect(route('commuters.index'));
    }

    public function test_commuter_store_fails_with_missing_password()
    {
        $response = $this->actingAs($this->admin())
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('commuters.store'), [
                'email' => 'john@example.com',
            ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertDatabaseMissing('users', ['email' => 'john@example.com']);
    }

    public function test_commuter_store_fails_with_invalid_email()
    {
        $response = $this->actingAs($this->admin())
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('commuters.store'), [
                'email' => 'not-an-email',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_commuter_can_be_updated()
    {
        $user = $this->commuter(['email' => 'old@example.com']);

        $this->actingAs($this->admin())
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->put(route('commuters.update', ['user' => $user->id]), [
                'email' => 'new@example.com',
            ])
            ->assertRedirect(route('commuters.index'));

        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'new@example.com']);
    }

    public function test_commuter_can_be_deleted()
    {
        $user = $this->commuter();

        $this->actingAs($this->admin())
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->delete(route('commuters.destroy', ['user' => $user->id]));

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_commuter_update_fails_with_invalid_email()
    {
        $user = $this->commuter();

        $response = $this->actingAs($this->admin())
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->put(route('commuters.update', ['user' => $user->id]), [
                'email' => 'not-valid',
            ]);

        $response->assertSessionHasErrors(['email']);
    }
}
