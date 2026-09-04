<?php

namespace Database\Factories;

use App\Models\Fare;
use Illuminate\Database\Eloquent\Factories\Factory;

class FareRateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'fare_id' => Fare::factory(),
            'km' => fake()->numberBetween(1, 50),
            'regular' => fake()->randomFloat(2, 10, 100),
            'discount' => fake()->randomFloat(2, 5, 50),
        ];
    }
}
