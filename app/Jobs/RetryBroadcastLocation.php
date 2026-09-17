<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RetryBroadcastLocation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $payload;
    public int $attempts = 0;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function handle(): void
    {
        if ($this->attempts >= 1) {
            return;
        }

        // Replay the broadcast through the controller
        app()->call(
            'App\Http\Controllers\VehicleTrackingController@broadcastLocation',
            ['request' => request()->create('/track/vehicle/broadcast', 'POST', $this->payload)]
        );
    }
}
