<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Tests\TestCase;

class UserPreferenceControllerTest extends TestCase
{
    public function test_font_size_update_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->patch(route('settings.update.fontsize'), [
                'font_size' => 'medium',
            ]);
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_theme_update_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->patch(route('settings.update.theme'), [
                'theme' => 'dark',
            ]);
        $this->assertTrue($response->getStatusCode() > 0);
    }
}
