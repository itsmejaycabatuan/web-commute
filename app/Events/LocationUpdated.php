<?php

namespace App\Events;

use App\Helpers\LocationPrivacy;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LocationUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public $trackingId;

    public $coordinates;

    public $obfuscatedLat;

    public $obfuscatedLng;

    public $privacyRadius;

    public $speed;

    public $accuracy;

    public $timestamp;

    public $userId;

    public function __construct($trackingId, $latitude, $longitude, $speed, $accuracy, $userId)
    {
        $private = LocationPrivacy::obfuscate($latitude, $longitude);

        $this->trackingId = $trackingId;
        $this->coordinates = [$private['lng'], $private['lat']];
        $this->obfuscatedLat = $private['lat'];
        $this->obfuscatedLng = $private['lng'];
        $this->privacyRadius = $private['privacy_radius'];
        $this->speed = $speed;
        $this->accuracy = $accuracy;
        $this->timestamp = now()->toDateTimeString();
        $this->userId = $userId;
    }

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
            'lat' => $this->obfuscatedLat,
            'lng' => $this->obfuscatedLng,
            'privacy_radius' => $this->privacyRadius,
            'speed' => $this->speed,
            'accuracy' => $this->accuracy,
            'timestamp' => $this->timestamp,
            'user_id' => $this->userId,
        ];
    }
}
