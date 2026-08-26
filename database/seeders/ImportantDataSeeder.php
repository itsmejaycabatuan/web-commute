<?php

namespace Database\Seeders;

use App\Models\Fare;
use App\Models\MaintenanceTask;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class ImportantDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $tasks = [
            [
                'tasks_performed' => 'Air Filter Change',
                'miles_between_service' => 10000,
                'months_between_service' => 6,
            ],
            [
                'tasks_performed' => 'Battery Replacement',
                'miles_between_service' => null,
                'months_between_service' => 36,
            ],
            [
                'tasks_performed' => 'Belt Replacement',
                'miles_between_service' => 50000,
                'months_between_service' => 24,
            ],
            [
                'tasks_performed' => 'Brake Pad Replacement',
                'miles_between_service' => 15000,
                'months_between_service' => null,
            ],
            [
                'tasks_performed' => 'Bulb Replacement',
                'miles_between_service' => null,
                'months_between_service' => null,
            ],
            [
                'tasks_performed' => 'Engine Coolant',
                'miles_between_service' => 30000,
                'months_between_service' => 18,
            ],
            [
                'tasks_performed' => 'Fuel Filter Change',
                'miles_between_service' => 25000,
                'months_between_service' => 12,
            ],
            [
                'tasks_performed' => 'Hose Replacement',
                'miles_between_service' => 60000,
                'months_between_service' => 48,
            ],
            [
                'tasks_performed' => 'Oil and Filter Change',
                'miles_between_service' => 3000,
                'months_between_service' => 3,
            ],
            [
                'tasks_performed' => 'Tire Alignment',
                'miles_between_service' => 6000,
                'months_between_service' => null,
            ],
            [
                'tasks_performed' => 'Tire Repare/Replacement',
                'miles_between_service' => 65000,
                'months_between_service' => 48,
            ],
            [
                'tasks_performed' => 'Tire Rotation/Balance',
                'miles_between_service' => 7500,
                'months_between_service' => 6,
            ],
            [
                'tasks_performed' => 'Transmission Fluid',
                'miles_between_service' => 50000,
                'months_between_service' => 24,
            ],
            [
                'tasks_performed' => 'Windshield Wiper Replacement',
                'miles_between_service' => null,
                'months_between_service' => 6,
            ],
        ];

        $vehicles = [
            [
                'vehicle_data' => [
                    'driver_id' => 1,
                    'year' => 2020,
                    'brand' => 'Toyota',
                    'model' => 'HiAce',
                    'plate_number' => 'JKL-7890',
                    'status' => 'maintenance',
                    'fuel_type' => 'diesel',
                    'tank_capacity' => 70.0,
                    'vin' => '5TDBW5G14ES567890',
                    'location' => 'Cebu Branch',
                    'acquisition_date' => '2020-11-01',
                    'exp_disposal_date' => '2025-11-01',
                ],
            ],
        ];

        foreach ($tasks as $task) {
            MaintenanceTask::create($task);
        }

        foreach ($vehicles as $item) {
            // Create the vehicle
            $vehicle = Vehicle::create($item['vehicle_data']);
        }

        $path = resource_path('files/Fare-Guide_Modernized-Aircon-Provisional-Fare-Increase_08Oct2023.pdf');

        DB::transaction(function () use ($path) {
            $fare = Fare::create([
                'location' => $path,
            ]);

            $isWindows = PHP_OS_FAMILY === 'Windows';

            $pythonPath = resource_path('scripts/extractPdf.py');
            $python = $isWindows
                ? base_path('venv\Scripts\python.exe')
                : base_path('venv/bin/python3');

            $command = sprintf(
                '"%s" "%s" "%s" 2>&1',
                $python,
                $pythonPath,
                $path
            );

            $result = shell_exec($command);

            if ($result === null) {
                throw new \RuntimeException('Python script failed to execute.');
            }

            $output = json_decode($result, true);

            if (! is_array($output)) {
                throw new \RuntimeException('Python script returned invalid JSON: ' . $result);
            }

            $rates = [];

            for ($i = 1; $i <= 25; $i++) {
                $rates[] = [
                    'fare_id' => $fare->id,
                    'km' => $output[$i]['km'],
                    'regular' => $output[$i]['regular'],
                    'discount' => $output[$i]['discount'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            for ($i = 27; $i < 52; $i++) {
                $rates[] = [
                    'fare_id' => $fare->id,
                    'km' => $output[$i]['km'],
                    'regular' => $output[$i]['regular'],
                    'discount' => $output[$i]['discount'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::table('fare_rates')->insert($rates);
        });
    }
}
