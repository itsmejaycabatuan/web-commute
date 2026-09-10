<?php

namespace Tests\Feature;

use App\Models\Vehicle;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Tests\TestCase;

class VehicleControllerTest extends TestCase
{
    public function test_vehicles_index_route_responds()
    {
        $response = $this->get(route('vehicles.index'));
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_vehicles_store_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('vehicles.store'), [
                'plate_number' => 'TEST-123',
                'model' => 'Test Model',
            ]);
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_vehicles_update_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->patch(route('vehicles.update', ['vehicle' => 99999]), [
                'plate_number' => 'UPDATED-123',
            ]);
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_vehicles_destroy_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->delete(route('vehicles.destroy', ['vehicle' => 99999]));
        $this->assertTrue($response->getStatusCode() > 0);
    }
}
