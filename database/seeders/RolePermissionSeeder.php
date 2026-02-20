<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
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
        Permission::create(['name' => 'view routes']);
        Permission::create(['name' => 'create routes']);
        Permission::create(['name' => 'edit routes']);
        Permission::create(['name' => 'delete routes']);

        // app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Roles Here
        $commuterRole = Role::create(['name' => 'commuter']);
        $driverRole = Role::create(['name'=> 'driver']);
        $adminRole = Role::create(['name' => 'admin']);
        $superAdmin = Role::create(['name'=> 'superadmin']);
        $ownerRole = Role::create(['name'=> 'owner']);

        //AssignPermissions Here
        //AdminPermissions
        $adminRole->givePermissionTo('view routes', 'create routes', 'edit routes', 'delete routes');

        // Create Users Here
        $markj = User::create([
            'email' => 'markjay.dev@proton.me',
            'password' => Hash::make('admin123'),
            'email_verified_at' => now(),
        ]);

        // Assign Roles to users here
        $markj->assignRole($adminRole);
    }
}
