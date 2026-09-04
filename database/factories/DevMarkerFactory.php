<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DevMarkerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->word(),
            'lat' => (string) fake()->latitude(),
            'lng' => (string) fake()->longitude(),
            'status' => fake()->randomElement(['active', 'inactive']),
        ];
    }
}
