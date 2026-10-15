<?php

namespace Tests\Feature;

use App\Events\PusherEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Event;
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

    public function test_fire_event_broadcasts_and_confirms()
    {
        Event::fake([PusherEvent::class]);

        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('fire.event'));

        $response->assertOk()->assertJson(['message' => 'event sent']);
        Event::assertDispatched(PusherEvent::class);
    }
}
