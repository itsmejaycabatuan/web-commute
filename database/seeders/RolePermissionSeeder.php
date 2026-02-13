<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
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

        // app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();f

        // Create Roles and Assign Permissions Here

        Role::create(['name' => 'commuter']);
        Role::create(['name'=> 'driver']);
        Role::create(['name'=> 'superadmin']);
        Role::create(['name'=> 'owner']);
        
    }
}
