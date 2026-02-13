<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserTest extends TestCase
{
    use DatabaseTransactions;
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_user_registration_page_can_be_rendered() {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_users_can_register() {
        $this->post(route('users.store'), [
            'email' => 'test@gmail.com',
            'password' => 'password123',
            'confirm-password' => 'password123',
            'terms' => '1'
        ]); 

        $this->assertDatabaseHas('users', ['email' => 'test@gmail.com']);
    }

    public function test_login_page_can_be_rendered() {
        $response = $this->get(route('login'));

        $response->assertOk();
    }

    public function test_users_can_login() {
        $user = User::factory()->create();

        $this->post(route('users.login'), [
            'email' => $user->email,
            'password' => '123456789',
        ]);
        
        $this->assertDatabaseHas('users', ['email'=> $user->email]);
    }

    public function test_users_can_logout() {
        $user = User::factory()->create();
        Auth::login($user);

        $response = $this->post(route('users.logout'));

        $response->assertFound(); 
    }

    public function test_reset_password_page_can_be_rendered() {
        $response = $this->get(route('password.request'));

        $response->assertOk();
    }

    public function test_users_can_request_reset_password_link() {
        $user = User::factory()->create();

        $response = $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $response->assertFound();
    }
}
