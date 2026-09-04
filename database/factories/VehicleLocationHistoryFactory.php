<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\VehicleLocation; // Ensure this model exists
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleLocationHistoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'vehicle_location_id' => VehicleLocation::factory(),
            'user_id' => User::factory(),
            'distance_from_last_pos' => (string) fake()->randomFloat(2, 0, 5),
            'latitude' => (string) fake()->latitude(),
            'longitude' => (string) fake()->longitude(),
            'created_at' => fake()->dateTime(),
        ];
    }
}
