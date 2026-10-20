<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\TimeKeeping;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleLocation;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Coverage for the driver suspension flow (UCN_SC_E012).
 */
class UcnSuspensionTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'driver_manager', 'maintenance_manager', 'driver'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
    }

    protected function admin(): User
    {
        return User::factory()->create()->assignRole('admin');
    }

    protected function maintenanceManager(): User
    {
        return User::factory()->create()->assignRole('maintenance_manager');
    }

    /** A driver user plus their driver profile. */
    protected function driverWithProfile(array $attributes = []): array
    {
        $user = User::factory()->create($attributes)->assignRole('driver');
        $user->markEmailAsVerified();

        $driver = Driver::factory()->create(array_merge(['user_id' => $user->id], $attributes['driver'] ?? []));

        return [$user, $driver];
    }

    // ───────────────────────── driver suspension ─────────────────────────

    public function test_a_driver_can_be_suspended()
    {
        [$user, $driver] = $this->driverWithProfile();

        $this->actingAs($this->admin())
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->patch(route('drivers.suspension', ['driver' => $driver->id]), [
                'suspension_reason' => 'Licence under investigation',
            ])
            ->assertSessionHas('success');

        $driver->refresh();

        $this->assertTrue($driver->is_suspended);
        $this->assertTrue($driver->isSuspended());
        $this->assertNotNull($driver->suspended_at);
        $this->assertSame('Licence under investigation', $driver->suspension_reason);
        $this->assertSame('suspended', $driver->status);
        $this->assertTrue($user->fresh()->isSuspended());
    }

    public function test_a_suspended_driver_cannot_sign_in()
    {
        [$user] = $this->driverWithProfile([
            'email' => 'suspended-driver@example.com',
            'password' => bcrypt('password123'),
        ]);

        $user->driver->suspend();

        auth()->logout();

        $this->post(route('users.login'), [
            'email' => 'suspended-driver@example.com',
            'password' => 'password123',
        ])->assertSessionHasErrors('credentials');

        $this->assertGuest();
    }

    public function test_a_suspended_driver_cannot_clock_in()
    {
        [$user, $driver] = $this->driverWithProfile();

        $driver->suspend();

        $before = TimeKeeping::count();

        $this->actingAs($user)
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('driver.timekeeping.clock-in'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertSame($before, TimeKeeping::count());
    }

    public function test_a_suspended_driver_cannot_broadcast_a_location()
    {
        [$user, $driver] = $this->driverWithProfile();
        $vehicle = Vehicle::factory()->create(['driver_id' => $driver->id]);

        $driver->suspend();

        $this->actingAs($user)
            ->postJson(route('vehicle.broadcast'), [
                'vehicle_id' => (string) $vehicle->id,
                'latitude' => 14.5995,
                'longitude' => 120.9842,
                'user_id' => $user->id,
            ])
            ->assertStatus(403);

        $this->assertSame(0, VehicleLocation::where('vehicle_id', $vehicle->id)->count());
    }

    public function test_suspending_a_driver_detaches_their_vehicle()
    {
        [, $driver] = $this->driverWithProfile();
        $vehicle = Vehicle::factory()->create(['driver_id' => $driver->id]);

        $driver->suspend();

        $this->assertNull($vehicle->fresh()->driver_id);
    }

    public function test_a_suspended_driver_can_be_reactivated()
    {
        [, $driver] = $this->driverWithProfile();
        $driver->suspend();

        $this->actingAs($this->admin())
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->patch(route('drivers.suspension', ['driver' => $driver->id]))
            ->assertSessionHas('success');

        $driver->refresh();

        $this->assertFalse($driver->is_suspended);
        $this->assertNull($driver->suspended_at);
        $this->assertSame('inactive', $driver->status);
    }

    public function test_a_driver_holding_a_vehicle_cannot_be_deleted()
    {
        [, $driver] = $this->driverWithProfile();
        Vehicle::factory()->create(['driver_id' => $driver->id]);

        $this->actingAs($this->admin())
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->delete(route('drivers.destroy', ['driver' => $driver->id]))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('drivers', ['id' => $driver->id]);
    }

    public function test_the_drivers_page_shows_the_suspension_state()
    {
        [, $driver] = $this->driverWithProfile();
        $driver->suspend();

        $response = $this->actingAs($this->admin())->get(route('drivers.index'));

        $response->assertOk();
        $this->assertTrue($response->viewData('drivers')->firstWhere('id', $driver->id)->is_suspended);
    }
}
