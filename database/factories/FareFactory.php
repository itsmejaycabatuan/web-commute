<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class FareFactory extends Factory
{
    public function definition(): array
    {
        return [
            'location' => fake()->city(),
        ];
    }
}
