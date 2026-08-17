<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class UserTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_user_registration_page_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_commuters_can_register()
    {
        $this->post(route('users.register'), [
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
        $user = User::factory()->create();
        Auth::login($user);

        $response = $this->post(route('users.logout'));

        $response->assertFound();
    }

    public function test_reset_password_request_page_can_be_rendered()
    {
        $response = $this->get(route('password.request'));

        $response->assertOk();
    }

    public function test_users_can_request_reset_password_link()
    {
        $user = User::factory()->create();

        $response = $this->post(route('password.email'), [
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
}
