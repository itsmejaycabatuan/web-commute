<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Ensures a default administrator account exists.
 *
 * Default credentials (local / development):
 *   Email:    admin@smartcommute.local
 *   Password: admin123
 *
 * Override the password with .env: DEFAULT_ADMIN_PASSWORD=your_secret
 *
 * Run alone if roles already exist: php artisan db:seed --class=DefaultAdminSeeder
 */
class DefaultAdminSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $adminRole = Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'web']
        );

        $password = env('DEFAULT_ADMIN_PASSWORD', 'admin123');

        $user = User::firstOrCreate(
            ['email' => 'admin@smartcommute.local'],
            [
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );

        if (! $user->hasRole('admin')) {
            $user->assignRole($adminRole);
        }
    }
}
