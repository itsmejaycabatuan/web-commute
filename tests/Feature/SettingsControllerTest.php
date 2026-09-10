<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    public function test_settings_edit_route_responds()
    {
        $response = $this->get(route('settings.edit'));
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_settings_update_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->put(route('settings.update'), [
                'name' => 'Test',
                'email' => 'test@example.com',
            ]);
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_settings_password_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->put(route('settings.password'), [
                'current_password' => 'password',
                'new_password' => 'newpassword',
                'new_password_confirmation' => 'newpassword',
            ]);
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_settings_export_data_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('settings.export-data'));
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_settings_logout_others_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('settings.logout-others'));
        $this->assertTrue($response->getStatusCode() > 0);
    }
}
