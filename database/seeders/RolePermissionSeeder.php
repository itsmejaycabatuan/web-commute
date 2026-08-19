<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\ViolationCode;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions Here
        // Routes Permission
        Permission::firstOrCreate(['name' => 'view routes']);
        Permission::firstOrCreate(['name' => 'create routes']);
        Permission::firstOrCreate(['name' => 'edit routes']);
        Permission::firstOrCreate(['name' => 'delete routes']);

        // app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Roles Here
        $commuterRole = Role::firstOrCreate(['name' => 'commuter']);
        $driverRole = Role::firstOrCreate(['name' => 'driver']);
        $driverManagerRole = Role::firstOrCreate(['name' => 'driver_manager']);
        $maintenanceManagerRole = Role::firstOrCreate(['name' => 'maintenance_manager']);
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $superAdmin = Role::firstOrCreate(['name' => 'superadmin']);
        $ownerRole = Role::firstOrCreate(['name' => 'owner']);

        // AssignPermissions Here
        // AdminPermissions
        $adminRole->givePermissionTo('view routes', 'create routes', 'edit routes', 'delete routes');

        // Create Users Here
        $markj = User::firstOrCreate(
            ['email' => 'markjay.dev@proton.me'],
            ['password' => Hash::make('admin123'),
                'email_verified_at' => now(), ]
        );

        $admin = User::firstOrCreate(
            [
                'email' => 'admin@gmail.com'],
            ['password' => Hash::make('admin123'),
                'email_verified_at' => now()]
        );

        $commuter = User::firstOrCreate(
            [
                'email' => 'commuter@gmail.com'],
            ['password' => Hash::make('admin123'),
                'email_verified_at' => now()]
        );

        Wallet::firstOrCreate([
            'user_id' => $commuter->id,
        ]);

        $driverUser = User::firstOrCreate(
            ['email' => 'driver@gmail.com'],
            ['password' => Hash::make('admin123'),
                'email_verified_at' => now(),
            ],
        );

        $driver = $driverUser->driver()->firstOrCreate([
            'name' => 'driver',
            'is_approved' => true,
        ]);

        $driverManager = User::firstOrCreate(
            ['email' => 'drivermanager@gmail.com'],
            [
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
            ]
        );

        $maintenanceManager = User::firstOrCreate(
            ['email' => 'maintenancemanager@gmail.com'],
            [
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
            ]
        );

        $violationCode = ViolationCode::firstOrCreate([
            'code' => 'UV01',
            'violation_name' => 'Disregarding Traffic Sign',
            'first_offense' => '1000',
            'second_offense' => '1000',
            'third_offense' => '1000',
            'fourth_offense' => '1000',
            'is_revoked' => false,
        ]);

        // Assign Roles to users here
        $markj->assignRole($adminRole);
        $admin->assignRole($adminRole);
        $commuter->assignRole($commuterRole);
        $driverUser->assignRole($driverRole);
        $driverManager->assignRole($driverManagerRole);
        $maintenanceManager->assignRole($maintenanceManagerRole);
    }
}
