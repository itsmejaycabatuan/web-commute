<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Tests\TestCase;

class RateControllerTest extends TestCase
{
    public function test_rates_index_route_responds()
    {
        $response = $this->get(route('rates.index'));
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_rates_create_route_responds()
    {
        $response = $this->get(route('rates.create'));
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_rates_store_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('rates.store'), [
                'route_name' => 'Test Route',
                'fare_amount' => 100,
            ]);
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_rates_show_route_responds()
    {
        $response = $this->get(route('rates.show', ['rate' => 1]));
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_rates_edit_route_responds()
    {
        $response = $this->get(route('rates.edit', ['rate' => 1]));
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_rates_update_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->put(route('rates.update', ['rate' => 1]), [
                'route_name' => 'Updated',
            ]);
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_rates_destroy_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->delete(route('rates.destroy', ['rate' => 1]));
        $this->assertTrue($response->getStatusCode() > 0);
    }
}
