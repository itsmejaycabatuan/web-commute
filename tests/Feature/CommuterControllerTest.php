<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CommuterControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure roles exist for tests
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'commuter']);
        Role::firstOrCreate(['name' => 'driver']);
    }

    /**
     * Helper to act as an Admin.
     */
    protected function actAsAdmin()
    {
        $admin = User::factory()->create()->assignRole('admin');

        return $this->actingAs($admin);
    }

    /**
     * INDEX TESTS
     */
    public function test_admin_can_view_commuters_list()
    {
        $commuter = User::factory()->create()->assignRole('commuter');
        $admin = User::factory()->create()->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('commuters.index'));

        $response->assertOk()
            ->assertViewHas('commuters')
            ->assertSee($commuter->email);
    }

    /**
     * STORE TESTS
     */
    public function test_admin_can_store_commuter()
    {
        $this->actAsAdmin()
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('commuters.store'), [
                'email' => 'newcommuter@test.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'mark_verified' => '1', // Simulating checkbox
            ]);

        $this->assertDatabaseHas('users', ['email' => 'newcommuter@test.com']);

        $user = User::where('email', 'newcommuter@test.com')->first();
        $this->assertTrue($user->hasRole('commuter'));
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_admin_can_store_unverified_commuter()
    {
        $this->actAsAdmin()
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('commuters.store'), [
                'email' => 'unverified@test.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'mark_verified' => null, // Checkbox unchecked
            ]);

        $user = User::where('email', 'unverified@test.com')->first();
        $this->assertNull($user->email_verified_at);
    }

    public function test_store_validation_fails_with_invalid_data()
    {
        $this->actAsAdmin()
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('commuters.store'), [
                'email' => 'not-an-email',
                'password' => '123', // Too short
            ])
            ->assertSessionHasErrors(['email', 'password']);
    }

    public function test_admin_cannot_edit_non_commuter_returns_404()
    {
        // Create a user with a different role (e.g., Driver)
        $driver = User::factory()->create()->assignRole('driver');

        $this->actAsAdmin()
            ->get(route('commuters.edit', $driver))
            ->assertStatus(404);
    }

    /**
     * UPDATE TESTS
     */
    public function test_admin_can_update_commuter_email()
    {
        $commuter = User::factory()->create()->assignRole('commuter');

        $this->actAsAdmin()
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->put(route('commuters.update', $commuter), [
                'email' => 'updated@test.com',
                'password' => '', // Password not required for update
                'password_confirmation' => '',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $commuter->id,
            'email' => 'updated@test.com',
        ]);
    }

    public function test_admin_can_update_commuter_password()
    {
        $commuter = User::factory()->create([
            'password' => Hash::make('oldpassword'),
        ])->assignRole('commuter');

        $this->actAsAdmin()
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->put(route('commuters.update', $commuter), [
                'email' => $commuter->email,
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        // Re-fetch user from DB
        $userFromDb = User::find($commuter->id);
        $this->assertTrue(Hash::check('newpassword123', $userFromDb->password));
    }

    public function test_update_fails_for_non_commuter()
    {
        $driver = User::factory()->create()->assignRole('driver');

        $this->actAsAdmin()
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->put(route('commuters.update', $driver), [
                'email' => 'hacked@test.com',
            ])
            ->assertStatus(404);
    }

    /**
     * DESTROY TESTS
     */
    public function test_admin_can_delete_commuter()
    {
        $commuter = User::factory()->create()->assignRole('commuter');

        $this->actAsAdmin()
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->delete(route('commuters.destroy', $commuter));

        $this->assertDatabaseMissing('users', ['id' => $commuter->id]);
    }

    public function test_admin_cannot_delete_themselves()
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->delete(route('commuters.destroy', $admin));

        // Assert redirect back with error
        $this->assertDatabaseHas('users', ['id' => $admin->id]); // User still exists
    }

    public function test_delete_fails_for_non_commuter()
    {
        $driver = User::factory()->create()->assignRole('driver');

        $this->actAsAdmin()
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->delete(route('commuters.destroy', $driver))
            ->assertStatus(404);
    }
}
