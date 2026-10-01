<?php

namespace App\Jobs;

use App\Events\LocationUpdated;
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

        // Re-publish the event directly. We can't re-enter the controller here
        // because the authorising driver is not part of this synthetic request.
        $p = $this->payload;

        if (! isset($p['vehicle_id'], $p['latitude'], $p['longitude'], $p['user_id'])) {
            return;
        }

        event(new LocationUpdated(
            $p['vehicle_id'],
            $p['latitude'],
            $p['longitude'],
            $p['speed'] ?? null,
            $p['accuracy'] ?? null,
            $p['user_id']
        ));
    }
}
