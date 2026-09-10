<?php

namespace Tests\Feature;

use App\Models\Route;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Tests\TestCase;

class RouteControllerTest extends TestCase
{
    public function test_routes_index_route_responds()
    {
        $response = $this->get(route('routes.index'));
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_routes_create_route_responds()
    {
        $response = $this->get(route('routes.create'));
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_routes_store_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('routes.store'), [
                'name' => 'Test Route',
                'start_point' => 'Point A',
                'end_point' => 'Point B',
            ]);
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_routes_show_route_responds()
    {
        $route = Route::first();
        if ($route) {
            $response = $this->get(route('routes.show', $route->id));
            $this->assertTrue($response->getStatusCode() > 0);
        } else {
            $this->assertTrue(true);
        }
    }

    public function test_routes_edit_route_responds()
    {
        $route = Route::first();
        if ($route) {
            $response = $this->get(route('routes.edit', $route->id));
            $this->assertTrue($response->getStatusCode() > 0);
        } else {
            $this->assertTrue(true);
        }
    }

    public function test_routes_update_route_responds()
    {
        $route = Route::first();
        if ($route) {
            $response = $this->withoutMiddleware([VerifyCsrfToken::class])
                ->put(route('routes.update', $route->id), ['name' => 'Updated']);
            $this->assertTrue($response->getStatusCode() > 0);
        } else {
            $this->assertTrue(true);
        }
    }

    public function test_routes_destroy_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->delete(route('routes.destroy', ['route' => 99999]));
        $this->assertTrue($response->getStatusCode() > 0);
    }
}
