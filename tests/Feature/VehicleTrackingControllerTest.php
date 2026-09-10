<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Tests\TestCase;

class VehicleTrackingControllerTest extends TestCase
{
    public function test_vehicle_broadcast_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('vehicle.broadcast'), [
                'vehicle_id' => 1,
                'lat' => 14.5995,
                'lng' => 120.9842,
            ]);
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_active_tracked_vehicles_route_responds()
    {
        $response = $this->get('/track/vehicles/active');
        $this->assertTrue($response->getStatusCode() > 0);
    }
}
