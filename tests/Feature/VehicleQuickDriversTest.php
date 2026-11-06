<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** The quick "Drivers" dialog on the Vehicles page: replace, add and remove drivers without the edit form. */
class VehicleQuickDriversTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'commuter', 'driver', 'driver_manager', 'maintenance_manager'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    private function manager(): User
    {
        return User::factory()->create(['email_verified_at' => now()])->assignRole('maintenance_manager');
    }

    private function driver(array $attrs = []): Driver
    {
        $user = User::factory()->create(['email_verified_at' => now()])->assignRole('driver');

        return Driver::factory()->approved()->create(array_merge(['user_id' => $user->id, 'is_suspended' => false], $attrs));
    }

    private function save(Vehicle $vehicle, array $body)
    {
        return $this->actingAs($this->manager())->patchJson(route('vehicles.drivers.update', $vehicle), $body);
    }

    public function test_a_driver_can_be_added_to_an_empty_vehicle()
    {
        $vehicle = Vehicle::factory()->create(['status' => 'active']);
        $driver = $this->driver();

        $this->save($vehicle, ['driver_id' => $driver->id])
            ->assertOk()
            ->assertJsonPath('vehicle.driver_ids', [$driver->id]);

        $this->assertSame([$driver->id], $vehicle->drivers()->pluck('drivers.id')->all());
    }

    public function test_a_driver_can_be_replaced_and_a_second_one_added()
    {
        $old = $this->driver();
        $new = $this->driver();
        $second = $this->driver();
        $vehicle = Vehicle::factory()->withDriver($old->id)->create(['status' => 'active']);

        $this->save($vehicle, ['driver_id' => $new->id, 'second_driver_id' => $second->id])->assertOk();

        $this->assertEqualsCanonicalizing([$new->id, $second->id], $vehicle->drivers()->pluck('drivers.id')->all());
        $this->assertDatabaseMissing('driver_vehicle', ['vehicle_id' => $vehicle->id, 'driver_id' => $old->id]);
    }

    public function test_clearing_both_slots_removes_the_drivers()
    {
        $a = $this->driver();
        $vehicle = Vehicle::factory()->withDriver($a->id)->create(['status' => 'active']);

        $this->save($vehicle, ['driver_id' => null, 'second_driver_id' => null])->assertOk();

        $this->assertSame(0, $vehicle->drivers()->count());
    }

    public function test_a_lone_second_driver_moves_up_to_the_first_seat()
    {
        $a = $this->driver();
        $vehicle = Vehicle::factory()->create(['status' => 'active']);

        $this->save($vehicle, ['second_driver_id' => $a->id])->assertOk()->assertJsonPath('vehicle.driver_id', $a->id);
    }

    public function test_a_driver_on_another_vehicle_is_refused()
    {
        $busy = $this->driver();
        Vehicle::factory()->withDriver($busy->id)->create(['status' => 'active']);
        $vehicle = Vehicle::factory()->create(['status' => 'active']);

        $this->save($vehicle, ['driver_id' => $busy->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('driver_id');
    }

    public function test_suspended_and_pending_drivers_are_refused()
    {
        $vehicle = Vehicle::factory()->create(['status' => 'active']);

        $this->save($vehicle, ['driver_id' => $this->driver(['is_suspended' => true])->id])->assertStatus(422);
        $this->save($vehicle, ['driver_id' => Driver::factory()->pending()->create()->id])->assertStatus(422);
        $this->assertSame(0, $vehicle->drivers()->count());
    }

    public function test_a_vehicle_out_of_service_takes_no_new_driver_but_can_lose_one()
    {
        $kept = $this->driver();
        $vehicle = Vehicle::factory()->withDriver($kept->id)->create(['status' => 'maintenance']);
        $free = $this->driver();

        $this->save($vehicle, ['driver_id' => $kept->id, 'second_driver_id' => $free->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('second_driver_id');

        $this->save($vehicle, ['driver_id' => null])->assertOk();
        $this->assertSame(0, $vehicle->drivers()->count());
    }

    public function test_the_same_driver_cannot_fill_both_seats()
    {
        $a = $this->driver();
        $vehicle = Vehicle::factory()->create(['status' => 'active']);

        $this->save($vehicle, ['driver_id' => $a->id, 'second_driver_id' => $a->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('second_driver_id');
    }

    public function test_only_maintenance_staff_may_use_it()
    {
        $vehicle = Vehicle::factory()->create(['status' => 'active']);
        $commuter = User::factory()->create(['email_verified_at' => now()])->assignRole('commuter');

        $this->actingAs($commuter)->patchJson(route('vehicles.drivers.update', $vehicle), [])->assertForbidden();
    }

    public function test_the_vehicles_page_offers_the_dialog()
    {
        $this->actingAs($this->manager())->get(route('vehicles.index'))
            ->assertOk()
            ->assertSee('openDriversModal', false)
            ->assertSee(route('vehicles.drivers.update', ':id', false), false);
    }
}
