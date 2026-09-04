<?php

namespace Database\Factories;

use App\Models\Driver;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VehicleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'driver_id' => Driver::factory(),
            'year' => fake()->numberBetween(2010, 2024),
            'brand' => fake()->randomElement(['Toyota', 'Honda', 'Ford', 'Hyundai']),
            'model' => fake()->word(),
            'plate_number' => strtoupper(fake()->bothify('???-###')),
            'status' => fake()->randomElement(['active', 'maintenance', 'retired']),
            'fuel_type' => fake()->randomElement(['Diesel', 'Gasoline', 'Electric']),
            'tank_capacity' => (string) fake()->numberBetween(40, 100),
            'vin' => strtoupper(Str::random(17)),
            'location' => fake()->city(),
            'acquisition_date' => fake()->date(),
            'exp_disposal_date' => fake()->dateTimeBetween('now', '+5 years')->format('Y-m-d'),
        ];
    }
}
