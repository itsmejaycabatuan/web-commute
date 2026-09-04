<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Driver;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure roles exist for tests
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'commuter']);
        Role::firstOrCreate(['name' => 'driver']);
        Role::firstOrCreate(['name' => 'driver_manager']);
        Role::firstOrCreate(['name' => 'maintenance_manager']);
    }

    /**
     * Helper to create a user with a specific role.
     */
    protected function createUserWithRole($roleName, $attributes = [])
    {
        $user = User::factory()->create($attributes);
        $user->assignRole($roleName);

        return $user;
    }

    public function test_user_registration_page_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_commuters_can_register()
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $response = $this->post(route('users.register'), [
            'email' => 'test@gmail.com',
            'password' => 'password123',
            'confirm-password' => 'password123',
            'terms' => '1',
        ]);

        $this->assertDatabaseHas('users', ['email' => 'test@gmail.com']);
    }

    public function test_driver_registration_page_can_be_rendered()
    {
        $response = $this->get(route('driver.register.page'));

        $response->assertOk();
    }

    public function test_drivers_can_register()
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $licenseImage = UploadedFile::fake()->image('license.jpg', 800, 600);

        $this->post(route('driver.register'), [
            'email' => 'testdriver@gmail.com',
            'contact_info' => '9984483912',
            'password' => 'password123',
            'confirm-password' => 'password123',
            'terms' => '1',
            'license_image' => $licenseImage,
        ]);

        $this->assertDatabaseHas('users', ['email' => 'testdriver@gmail.com']);

    }

    public function test_login_page_can_be_rendered()
    {
        $response = $this->get(route('login'));

        $response->assertOk();
    }

    public function test_users_can_login()
    {
        $user = User::factory()->create();

        $this->post(route('users.login'), [
            'email' => $user->email,
            'password' => '123456789',
        ]);

        $this->assertDatabaseHas('users', ['email' => $user->email]);
    }

    public function test_users_can_logout()
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $user = User::factory()->create();
        Auth::login($user);

        $response = $this->post(route('users.logout'));

        $response->assertRedirect(route('login'));
    }

    public function test_reset_password_request_page_can_be_rendered()
    {
        $response = $this->get(route('password.request'));

        $response->assertOk();
    }

    public function test_users_can_request_reset_password_link()
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $response->assertFound();
    }

    public function test_users_change_password_page_can_be_rendered()
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $response = $this->get(route('password.reset', ['token' => $token]));
        $response->assertOk();
    }

    public function test_users_can_change_password()
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $user = User::factory()->create();
        $token = Password::createToken($user);
        $newPassword = 'newPassword123';

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => $newPassword,
            'confirm-password' => $newPassword,
        ]);

        $this->assertTrue(
            auth()->validate([
                'email' => $user->email,
                'password' => $newPassword,
            ])
        );
    }

    /**
     * MAP TESTS
     */
    public function test_guest_can_view_map()
    {
        $response = $this->get(route('map'));
        $response->assertOk()->assertViewHas('rates');
    }

    public function test_commuter_can_view_map()
    {
        $user = $this->createUserWithRole('commuter');
        Wallet::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('map'));

        $response->assertOk()
            ->assertViewHas('rates')
            ->assertViewHas('balance')
            ->assertViewHas('recentReceipts');
    }

    public function test_admin_can_view_map()
    {
        $user = $this->createUserWithRole('admin');

        $response = $this->actingAs($user)->get(route('map'));

        $response->assertOk()
            ->assertViewHas('rates')
            ->assertViewHas('dummyMarkers');
    }

    public function test_driver_can_view_map_if_approved()
    {
        $user = $this->createUserWithRole('driver');

        // Create driver record and set status to approved
        Driver::factory()->create([
            'user_id' => $user->id,
            'is_approved' => true,
            'is_rejected' => false,
        ]);

        $response = $this->actingAs($user)->get(route('map'));

        $response->assertOk()
            ->assertViewHas('driverStatus');
    }

    public function test_pending_driver_is_logged_out_when_visiting_map()
    {
        $user = $this->createUserWithRole('driver');

        Driver::factory()->create([
            'user_id' => $user->id,
            'is_approved' => false,
            'is_rejected' => false,
        ]);

        $response = $this->actingAs($user)->get(route('map'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('driver_pending');
        $this->assertGuest();
    }

    public function test_rejected_driver_is_logged_out_when_visiting_map()
    {
        $user = $this->createUserWithRole('driver');

        Driver::factory()->create([
            'user_id' => $user->id,
            'is_approved' => false,
            'is_rejected' => true,
        ]);

        $response = $this->actingAs($user)->get(route('map'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('driver_rejected');
        $this->assertGuest();
    }

    /**
     * DASHBOARD TESTS
     */
    public function test_admin_can_view_dashboard()
    {
        $user = $this->createUserWithRole('admin');

        $response = $this->actingAs($user)->get(route('dashboard')); // Assuming route name is 'dashboard'

        $response->assertOk()
            ->assertViewIs('admin.dashboard')
            ->assertViewHas('totalRevenue');
    }

    public function test_driver_can_view_dashboard()
    {
        $user = $this->createUserWithRole('driver');
        Driver::factory()->create([
            'user_id' => $user->id,
            'is_approved' => true,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()
            ->assertViewIs('driver.dashboard')
            ->assertViewHas('driver')
            ->assertViewHas('violationLogs');
    }

    public function test_maintenance_manager_can_view_dashboard()
    {
        $user = $this->createUserWithRole('maintenance_manager');
        // Ensure at least one vehicle exists for the dashboard logic to pass smoothly
        Vehicle::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()
            ->assertViewIs('maintenance-manager.dashboard');
    }

    /**
     * PROFILE TESTS
     */
    public function test_commuter_can_view_profile()
    {
        $user = $this->createUserWithRole('commuter');
        Wallet::factory()->create(['user_id' => $user->id]);
        Payment::factory()->create(['paid_by' => $user->id]);

        $response = $this->actingAs($user)->get(route('profile')); // Assuming route name is 'profile'

        $response->assertOk()
            ->assertViewHas('wallet')
            ->assertViewHas('payments');
    }

    public function test_user_can_update_password_in_profile()
    {
        $user = $this->createUserWithRole('commuter');

        $this->withoutMiddleware(VerifyCsrfToken::class);

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'current_password' => '123456789',
            'password' => 'newPassword123',
            'password_confirmation' => 'newPassword123',
        ]);

        $this->assertTrue(Hash::check('newPassword123', $user->fresh()->password));
    }

    public function test_user_can_update_password()
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $user = $this->createUserWithRole('commuter');

        $token = Password::createToken($user);

        $response = $this->post(route('password.update'), [ // Assuming route name
            'token' => $token,
            'email' => $user->email,
            'current_password' => '123456789', // Default factory password
            'password' => 'newPassword123',
            'confirm-password' => 'newPassword123',
        ]);

        $this->assertTrue(Hash::check('newPassword123', $user->fresh()->password));
    }

    public function test_user_cannot_update_password_with_wrong_current_password()
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $user = $this->createUserWithRole('commuter');

        $token = Password::createToken($user);

        $response = $this->post(route('profile.update'), [
            'token' => $token,
            'email' => $user->email,
            'current_password' => 'wrong-password',
            'password' => 'newPassword123',
            'confirm-password' => 'newPassword123',
        ]);

        $this->assertFalse(Hash::check('newPassword123', $user->fresh()->password));
    }

    public function test_user_can_delete_account()
    {
        $user = $this->createUserWithRole('commuter');

        $this->withoutMiddleware(VerifyCsrfToken::class);

        $response = $this->actingAs($user)->delete(route('users.delete-account')); // Assuming route name

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertGuest();
    }

    /**
     * REGISTRATION & LOGIN EDGE CASES
     */
    public function test_registration_requires_valid_data()
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $response = $this->post(route('users.register'), [
            'email' => 'not-an-email',
            'password' => '123',
        ]);

        $response->assertSessionHasErrors(['email', 'password']);
    }

    public function test_login_fails_with_invalid_credentials()
    {
        $user = User::factory()->create(['password' => Hash::make('secret')]);
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $response = $this->post(route('users.login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertEquals('Error logging in', session('error'));
    }

    /**
     * EMAIL VERIFICATION
     */
    public function test_email_verification_view_can_be_rendered()
    {
        Mail::fake(); // Prevent actual email sending

        $user = $this->createUserWithRole('commuter');

        $response = $this->actingAs($user)->get(route('verification.notice')); // Assuming route name

        $response->assertViewIs('auth.verify-email');
    }
}
