<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'paid_by' => User::factory(),
            'transaction_id' => 'TXN-' . fake()->unique()->numerify('##########'),
            'starting_point' => fake()->streetAddress(),
            'destination' => fake()->streetAddress(),
            'total_distance' => (string) fake()->randomFloat(2, 1, 50),
            'is_discounted' => fake()->numberBetween(0, 1),
            'payment_method' => fake()->randomElement(['cash', 'wallet', 'gcash']),
            'price' => (string) fake()->randomFloat(2, 10, 1000),
            'paid_at' => fake()->dateTime()->format('Y-m-d H:i:s'),
        ];
    }
}
