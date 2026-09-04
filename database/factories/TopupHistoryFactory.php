<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

class TopupHistoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'wallet_id' => Wallet::factory(),
            'amount_added' => (string) fake()->randomFloat(2, 100, 5000),
            'payment_method' => fake()->randomElement(['credit_card', 'debit_card', 'gcash']),
        ];
    }
}
