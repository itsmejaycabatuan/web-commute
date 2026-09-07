<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\VehicleLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleLocationHistoryFactory extends Factory
{
    public function definition(): array
    {
        $vehicleLocation = VehicleLocation::create([
            'vehicle_id' => 'VH-' . fake()->unique()->numerify('######'),
            'user_id' => null,
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'last_update' => now()->toDateTimeString(),
        ]);

        return [
            'vehicle_location_id' => $vehicleLocation->id,
            'user_id' => User::factory(),
            'distance_from_last_pos' => (string) fake()->randomFloat(2, 0, 5),
            'latitude' => (string) fake()->latitude(),
            'longitude' => (string) fake()->longitude(),
            'created_at' => fake()->dateTime(),
        ];
    }
}