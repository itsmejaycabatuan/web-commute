<?php

namespace Tests\Feature\Nfr;

use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Base for the non-functional-requirement tests (docs/Non_Functional_Requirements.md).
 *
 * Every test in tests/Feature/Nfr carries `@group NFR-<area>-<nn>` naming the requirement it is the
 * evidence for, so one requirement's evidence runs on its own:
 *
 *     php vendor/bin/phpunit --group NFR-SEC-03
 *     php vendor/bin/phpunit --group NFR-SEC          # a whole category
 *
 * A test also tagged `@group nfr-gap` asserts the behaviour a requirement *wants* but the system does
 * not yet have. It FAILS today, is excluded from the default run (phpunit.xml), and starts passing the
 * day the gap is fixed:
 *
 *     php vendor/bin/phpunit --group nfr-gap
 */
abstract class NfrTestCase extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'driver_manager', 'maintenance_manager', 'driver', 'commuter'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
    }

    protected function userWithRole(string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes)->assignRole($role);
        $user->markEmailAsVerified();

        return $user->fresh();
    }

    protected function commuter(float $balance = 0): User
    {
        $user = $this->userWithRole('commuter');
        Wallet::factory()->create(['user_id' => $user->id, 'balance' => (string) $balance]);

        return $user->fresh();
    }

    /** @return array{0:User,1:Driver,2:Vehicle} an approved driver, their account and an active vehicle */
    protected function driverWithVehicle(array $vehicle = []): array
    {
        $driver = Driver::factory()->approved()->create();
        $user = User::find($driver->user_id)->assignRole('driver');
        $user->markEmailAsVerified();
        $car = Vehicle::factory()->withDriver($driver->id)->create($vehicle + ['status' => 'active']);

        return [$user->fresh(), $driver, $car];
    }

    /** Great-circle distance in metres. */
    protected function metres(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $r * asin(min(1, sqrt($a)));
    }
}
