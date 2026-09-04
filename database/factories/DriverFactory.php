<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DriverFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'driver_code' => 'DRV-' . fake()->unique()->numerify('######'),
            'name' => fake()->name(),
            'expiration_date' => fake()->date(),
            'contact_info' => fake()->phoneNumber(),
            'license_number' => fake()->numerify('##########'),
            'license_code' => fake()->randomElement(['PRO', 'NON-PRO']),
            'license_image_path' => 'images/license/' . fake()->uuid() . '.jpg',
            'license_status' => fake()->randomElement(['valid', 'expired', 'suspended']),
            'is_approved' => fake()->numberBetween(0, 1),
            'is_rejected' => fake()->numberBetween(0, 1),
            'status' => fake()->randomElement(['active', 'inactive', 'on_leave']),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_approved' => 1,
            'is_rejected' => 0,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_approved' => 0,
            'is_rejected' => 0,
        ]);
    }
}
