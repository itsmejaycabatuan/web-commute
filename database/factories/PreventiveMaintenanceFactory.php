<?php

namespace Database\Factories;

use App\Models\MaintenanceTask;
use App\Models\Vehicle; // Ensure this model exists
use Illuminate\Database\Eloquent\Factories\Factory;

class PreventiveMaintenanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'task_id' => MaintenanceTask::factory(),
            'last_service_odo' => fake()->numberBetween(5000, 100000),
            'last_service_date' => fake()->date(),
            'last_service_cost' => (string) fake()->randomFloat(2, 500, 5000),
            'comments' => fake()->sentence(),
        ];
    }
}
