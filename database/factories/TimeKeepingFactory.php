<?php

namespace Database\Factories;

use App\Models\Driver;
use Illuminate\Database\Eloquent\Factories\Factory;

class TimeKeepingFactory extends Factory
{
    public function definition(): array
    {
        $timeIn = fake()->time('H:i:s');
        $timeOut = fake()->boolean(80) ? fake()->time('H:i:s') : null;

        return [
            'driver_id' => Driver::factory(),
            'date' => fake()->date(),
            'time_in' => $timeIn,
            'time_out' => $timeOut,
            'hours_worked' => $timeOut ? fake()->randomFloat(1, 4, 10) : null,
            'overtime_hours' => $timeOut ? fake()->randomFloat(1, 0, 4) : null,
            'sick' => fake()->numberBetween(0, 1),
            'vacation' => fake()->numberBetween(0, 1),
        ];
    }
}
