<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Tests\TestCase;

class DevMarkerControllerTest extends TestCase
{
    public function test_dev_add_marker_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('driver.dev.add-marker'), [
                'lat' => 14.5995,
                'lng' => 120.9842,
                'type' => 'test',
            ]);
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_dev_clear_markers_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->delete(route('driver.dev.clear-markers'));
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_dev_remove_marker_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->delete(route('driver.dev.remove-marker', ['marker' => 1]));
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_dev_toggle_marker_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('driver.dev.toggle-marker', ['marker' => 1]));
        $this->assertTrue($response->getStatusCode() > 0);
    }
}
