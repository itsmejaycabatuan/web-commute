<?php

namespace Database\Factories;

use App\Models\VehicleLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleLocationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'driver_id' => null,
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
        ];
    }
}