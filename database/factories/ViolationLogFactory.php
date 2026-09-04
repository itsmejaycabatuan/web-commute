<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\ViolationCode; // Ensure this model exists
use Illuminate\Database\Eloquent\Factories\Factory;

class ViolationLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'vc_id' => ViolationCode::factory(),
            'violation_instance' => (string) fake()->numberBetween(1, 10),
            'violation_fine' => (string) fake()->randomFloat(2, 100, 5000),
            'additional_penalties' => (string) fake()->randomFloat(2, 0, 500),
            'date_of_violation' => fake()->date(),
            'time_of_violation' => fake()->time(),
            'place_of_violation' => fake()->streetAddress(),
            'remarks' => fake()->sentence(),
        ];
    }
}
