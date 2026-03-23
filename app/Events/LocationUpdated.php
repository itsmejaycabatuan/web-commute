<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $trackingId;
    public $coordinates;
    public $speed;
    public $heading;
    public $accuracy;
    public $timestamp;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($trackingId, $latitude, $longitude, $speed, $accuracy)
    {
        $this->trackingId = $trackingId;
        $this->coordinates = [$longitude, $latitude];
        $this->speed = $speed;
        $this->accuracy = $accuracy;
        $this->timestamp = now()->toDateTimeString();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('vehicle-locations');
    }

    public function broadcastAs(): string
    {
        return 'vehicle-location-updated';
    }

    public function broadcastWith()
    {
        return [
            'vehicleId' => $this->trackingId,
            'coordinates' => $this->coordinates,
            'speed' => $this->speed,
            'accuracy' => $this->accuracy,
            'timestamp' => $this->timestamp
        ];
    }
}
