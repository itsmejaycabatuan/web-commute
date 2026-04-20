<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions Here
        // Routes Permission
        Permission::firstOrCreate(['name' => 'view routes']);
        Permission::firstOrCreate(['name' => 'create routes']);
        Permission::firstOrCreate(['name' => 'edit routes']);
        Permission::firstOrCreate(['name' => 'delete routes']);

        // app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Roles Here
        $commuterRole = Role::firstOrCreate(['name' => 'commuter']);
        $driverRole = Role::firstOrCreate(['name'=> 'driver']);
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $superAdmin = Role::firstOrCreate(['name'=> 'superadmin']);
        $ownerRole = Role::firstOrCreate(['name'=> 'owner']);

        //AssignPermissions Here
        //AdminPermissions
        $adminRole->givePermissionTo('view routes', 'create routes', 'edit routes', 'delete routes');

        // Create Users Here
        $markj = User::firstOrCreate(
            ['email' => 'markjay.dev@proton.me'],
            ['password' => Hash::make('admin123'),
            'email_verified_at' => now(),]
        );

        $admin = User::firstOrCreate([
            'email' => 'admin@gmail.com'],
            ['password' => Hash::make('admin123'),
            'email_verified_at' => now()]
        );

        $commuter = User::firstOrCreate([
            'email' => 'commuter@gmail.com'],
            ['password' => Hash::make('admin123'),
            'email_verified_at' => now()]
        );

        Wallet::firstOrCreate([
            'user_id' => $commuter->id
        ]);

        $driver = User::firstOrCreate(
            ['email' => 'driver@gmail.com'],
            ['password' => Hash::make('admin123'),
            'email_verified_at' => now(),
            'license_number' => 'DEMO-LIC-001',
            'license_code' => 'A',
            'license_image_path' => null,
            'driver_approval_status' => 'approved'],
        );

        // Assign Roles to users here
        $markj->assignRole($adminRole);
        $admin->assignRole($adminRole);
        $commuter->assignRole($commuterRole);
        $driver->assignRole($driverRole);
    }
}
