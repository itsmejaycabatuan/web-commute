<?php

namespace Database\Factories;

use App\Models\ViolationCode;
use Illuminate\Database\Eloquent\Factories\Factory;

class ViolationCodeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'OV-' . fake()->unique()->numerify('00'),
            'violation_name' => fake()->word,
            'first_offense' => fake()->randomFloat(2, 100, 5000),
            'second_offense' => fake()->randomFloat(2, 100, 5000),
            'third_offense' => fake()->randomFloat(2, 100, 5000),
            'is_revoked' => 0,
        ];
    }
}