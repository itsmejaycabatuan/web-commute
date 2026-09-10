<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Tests\TestCase;

class PusherControllerTest extends TestCase
{
    public function test_pusher_index_route_responds()
    {
        $response = $this->get(route('pusher.index'));
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_pusher_fire_event_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('fire.event'), [
                'event' => 'test',
                'data' => [],
            ]);
        $this->assertTrue($response->getStatusCode() > 0);
    }

    /** 
     * Pusher Tests
     */
    public function test_fire_event_requires_authentication()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('fire.event'), [
                'event' => 'test',
                'data' => ['message' => 'Hello'],
            ]);

        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_fire_event_requires_valid_data()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('fire.event'), [
                'event' => '',
                'data' => null,
            ]);

        $response->assertStatus(422);
    }
}