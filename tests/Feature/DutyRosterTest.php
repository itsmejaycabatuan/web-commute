<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Driver;
use App\Models\TimeKeeping;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDutyDay;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Two drivers share a vehicle and take turns day by day. The Maintenance Manager keeps the duty roster
 * (generates it, swaps single days); a driver can only go on shift on a day the roster gives to them.
 */
class DutyRosterTest extends TestCase
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

    protected function manager(): User
    {
        return User::factory()->create(['email_verified_at' => now()])->assignRole('maintenance_manager');
    }

    /** @return array{0: User, 1: Driver} */
    protected function driver(string $status = 'inactive'): array
    {
        $user = User::factory()->create(['email_verified_at' => now()])->assignRole('driver');
        $driver = Driver::factory()->approved()->create(['user_id' => $user->id, 'status' => $status, 'is_suspended' => false]);

        return [$user->fresh(), $driver];
    }

    /** @return array{0: Vehicle, 1: array, 2: array} a vehicle shared by two drivers */
    protected function shared(): array
    {
        $a = $this->driver();
        $b = $this->driver();
        $vehicle = Vehicle::factory()->withDriver($a[1]->id, $b[1]->id)->create(['status' => 'active']);

        return [$vehicle, $a, $b];
    }

    protected function roster(Vehicle $vehicle, Driver $driver, ?string $date = null): void
    {
        VehicleDutyDay::updateOrCreate(
            ['vehicle_id' => $vehicle->id, 'duty_date' => $date ?? today()->toDateString()],
            ['driver_id' => $driver->id]
        );
    }

    // ───────────── clocking in on a shared vehicle ─────────────

    public function test_the_rostered_driver_can_clock_in_and_the_co_driver_cannot()
    {
        [$vehicle, [$userA, $a], [$userB, $b]] = $this->shared();
        $this->roster($vehicle, $a);

        $this->actingAs($userB)->post(route('driver.timekeeping.clock-in'))
            ->assertSessionHas('error');
        $this->assertSame(0, TimeKeeping::where('driver_id', $b->id)->count());

        $this->actingAs($userA)->post(route('driver.timekeeping.clock-in'))->assertSessionHas('success');
        $shift = TimeKeeping::where('driver_id', $a->id)->firstOrFail();
        $this->assertSame($vehicle->id, $shift->vehicle_id, 'the shift records which vehicle it was worked on');
    }

    public function test_the_refusal_names_the_co_driver_whose_day_it_is()
    {
        [$vehicle, [$userA, $a], [$userB, $b]] = $this->shared();
        $this->roster($vehicle, $a);

        $this->actingAs($userB)->post(route('driver.timekeeping.clock-in'))
            ->assertSessionHas('error', fn ($m) => str_contains($m, $a->name));
    }

    public function test_a_shared_vehicle_with_no_roster_line_for_today_cannot_be_driven()
    {
        [$vehicle, [$userA, $a]] = $this->shared();
        $this->roster($vehicle, $a, today()->addDay()->toDateString());

        $this->actingAs($userA)->post(route('driver.timekeeping.clock-in'))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'No duty roster'));
    }

    public function test_the_availability_toggle_follows_the_same_rule()
    {
        [$vehicle, [$userA, $a], [$userB, $b]] = $this->shared();
        $this->roster($vehicle, $a);

        $this->actingAs($userB)->post(route('driver.status.update'), ['status' => 'active'])->assertSessionHas('error');
        $this->assertSame('inactive', $b->fresh()->status);

        $this->actingAs($userA)->post(route('driver.status.update'), ['status' => 'active'])->assertSessionHas('success');
        $this->assertSame('active', $a->fresh()->status);

        // Going inactive is always allowed.
        $b->update(['status' => 'active']);
        $this->actingAs($userB)->post(route('driver.status.update'), ['status' => 'inactive'])->assertSessionHas('success');
    }

    public function test_a_vehicle_with_a_single_driver_needs_no_roster()
    {
        [$user, $driver] = $this->driver();
        Vehicle::factory()->withDriver($driver)->create(['status' => 'active']);

        $this->actingAs($user)->post(route('driver.timekeeping.clock-in'))->assertSessionHas('success');
    }

    public function test_swapping_a_day_hands_the_vehicle_to_the_other_driver()
    {
        [$vehicle, [$userA, $a], [$userB, $b]] = $this->shared();
        $this->roster($vehicle, $a);
        $this->roster($vehicle, $b); // the manager swaps today

        $this->actingAs($userA)->post(route('driver.timekeeping.clock-in'))->assertSessionHas('error');
        $this->actingAs($userB)->post(route('driver.timekeeping.clock-in'))->assertSessionHas('success');
    }

    public function test_the_map_tells_the_driver_it_is_not_their_day()
    {
        [$vehicle, [$userA, $a], [$userB, $b]] = $this->shared();
        $this->roster($vehicle, $a);

        $html = $this->actingAs($userB)->get(route('map'))->assertOk()->getContent();
        $this->assertStringContainsString('id="no-vehicle-notice"', $html);
        $this->assertStringContainsString($a->name, $html);

        $this->assertStringNotContainsString('id="no-vehicle-notice"', $this->actingAs($userA)->get(route('map'))->getContent());
    }

    public function test_only_the_driver_on_duty_may_broadcast_the_vehicles_location()
    {
        [$vehicle, [$userA, $a], [$userB, $b]] = $this->shared();
        $this->roster($vehicle, $a);
        $payload = fn ($u) => ['vehicle_id' => (string) $vehicle->id, 'latitude' => 10.3, 'longitude' => 123.9, 'speed' => 10, 'accuracy' => 5, 'user_id' => $u->id];

        $this->actingAs($userB)->postJson(route('vehicle.broadcast'), $payload($userB))->assertStatus(403)
            ->assertJsonPath('message', 'Today is not your duty day on this vehicle.');
        $this->assertNotSame(403, $this->actingAs($userA)->postJson(route('vehicle.broadcast'), $payload($userA))->status());
    }

    public function test_the_dashboard_shows_the_co_driver_and_the_next_duty_day()
    {
        [$vehicle, [$userA, $a], [$userB, $b]] = $this->shared();
        $this->roster($vehicle, $a, today()->addDays(3)->toDateString());
        $this->roster($vehicle, $b);

        $response = $this->actingAs($userA)->get(route('dashboard'))->assertOk();

        $response->assertSee('Shared', false)->assertSee($b->name);
        $this->assertEquals(today()->addDays(3)->toDateString(), $response->viewData('nextDuty')->toDateString());
    }

    // ───────────── assigning two drivers (Vehicles page) ─────────────

    protected function payload(array $extra = []): array
    {
        return $extra + [
            'year' => 2022, 'brand' => 'Toyota', 'model' => 'Hiace', 'plate_number' => 'SH-'.random_int(1000, 9999),
            'status' => 'active', 'acquisition_date' => '2022-01-01',
        ];
    }

    public function test_a_vehicle_can_be_given_two_drivers_but_not_the_same_one_twice_or_a_third()
    {
        $manager = $this->manager();
        [, $a] = $this->driver();
        [, $b] = $this->driver();

        $this->actingAs($manager)->postJson(route('vehicles.store'), $this->payload(['driver_id' => $a->id, 'second_driver_id' => $a->id]))
            ->assertStatus(422)->assertJsonValidationErrors('second_driver_id');

        $response = $this->actingAs($manager)->postJson(route('vehicles.store'), $this->payload(['driver_id' => $a->id, 'second_driver_id' => $b->id]))
            ->assertCreated()
            ->assertJsonPath('vehicle.driver_id', fn ($v) => in_array($v, [$a->id, $b->id]));

        $vehicle = Vehicle::find($response->json('vehicle.id'));
        $this->assertSame(2, $vehicle->drivers()->count());
        $this->assertStringContainsString(' & ', $response->json('vehicle.driver_name'));
        $this->assertCount(2, $response->json('vehicle.driver_ids'));
    }

    public function test_a_driver_already_on_a_vehicle_cannot_be_the_second_driver_of_another()
    {
        $manager = $this->manager();
        [, $a] = $this->driver();
        [, $b] = $this->driver();
        Vehicle::factory()->withDriver($b)->create(['status' => 'active']);

        $this->actingAs($manager)->postJson(route('vehicles.store'), $this->payload(['driver_id' => $a->id, 'second_driver_id' => $b->id]))
            ->assertStatus(422)->assertJsonValidationErrors('second_driver_id');
    }

    public function test_removing_a_driver_drops_them_from_the_roster_and_a_single_driver_needs_none()
    {
        $manager = $this->manager();
        [$vehicle, [, $a], [, $b]] = $this->shared();
        $this->roster($vehicle, $a);
        $this->roster($vehicle, $b, today()->addDay()->toDateString());
        $past = today()->subDays(2)->toDateString();
        $this->roster($vehicle, $b, $past);

        $this->actingAs($manager)->patchJson(route('vehicles.update', $vehicle), $this->payload([
            'plate_number' => $vehicle->plate_number, 'driver_id' => $a->id, 'second_driver_id' => null,
        ]))->assertOk();

        $this->assertSame([$a->id], $vehicle->fresh()->drivers()->pluck('drivers.id')->all());
        $this->assertSame(0, VehicleDutyDay::where('vehicle_id', $vehicle->id)->whereDate('duty_date', '>=', today())->count());
        $this->assertSame(1, VehicleDutyDay::where('vehicle_id', $vehicle->id)->whereDate('duty_date', $past)->count(), 'history stays');
    }

    public function test_taking_a_shared_vehicle_out_of_service_detaches_both_drivers()
    {
        $manager = $this->manager();
        [$vehicle] = $this->shared();

        $this->actingAs($manager)->patchJson(route('vehicles.update', $vehicle), $this->payload([
            'plate_number' => $vehicle->plate_number, 'status' => 'maintenance',
        ]))->assertOk();

        $this->assertSame(0, $vehicle->fresh()->drivers()->count());
    }

    public function test_a_shared_vehicle_cannot_be_deleted_or_disposed_while_either_driver_is_on_shift()
    {
        $manager = $this->manager();
        [$vehicle, [, $a], [, $b]] = $this->shared();

        $this->actingAs($manager)->deleteJson(route('vehicles.destroy', $vehicle))->assertStatus(409);

        TimeKeeping::create(['driver_id' => $b->id, 'date' => today()->toDateString(), 'time_in' => '08:00 AM']);
        $this->actingAs($manager)->patchJson(route('vehicles.update', $vehicle), $this->payload([
            'plate_number' => $vehicle->plate_number, 'status' => 'disposed',
            'driver_id' => $a->id, 'second_driver_id' => $b->id,
        ]))->assertStatus(422)->assertJsonValidationErrors('status');
    }

    public function test_suspending_a_driver_leaves_the_co_driver_on_the_vehicle()
    {
        [$vehicle, [, $a], [, $b]] = $this->shared();
        $this->roster($vehicle, $a);

        $a->suspend('test');

        $this->assertSame([$b->id], $vehicle->fresh()->drivers()->pluck('drivers.id')->all());
        $this->assertSame(0, VehicleDutyDay::where('driver_id', $a->id)->whereDate('duty_date', '>=', today())->count());
    }

    // ───────────── the roster page ─────────────

    public function test_only_the_maintenance_manager_opens_and_edits_the_roster()
    {
        [$vehicle] = $this->shared();

        $this->actingAs($this->manager())->get(route('maintenance-manager.duty-roster'))->assertOk()->assertSee('Duty');

        foreach (['admin', 'driver_manager', 'commuter', 'driver'] as $role) {
            $user = User::factory()->create(['email_verified_at' => now()])->assignRole($role);
            $this->actingAs($user)->get(route('maintenance-manager.duty-roster'))->assertStatus(403);
            $this->actingAs($user)->put(route('maintenance-manager.duty-roster.update', $vehicle), ['days' => []])->assertStatus(403);
            $this->actingAs($user)->post(route('maintenance-manager.duty-roster.generate', $vehicle), [])->assertStatus(403);
        }
        $this->assertContains('maintenance-manager.duty-roster', collect(config('menu.menus.maintenance_manager'))->pluck('route')->filter()->all());
    }

    public function test_the_page_lists_only_shared_vehicles_with_todays_driver()
    {
        [$vehicle, [, $a]] = $this->shared();
        $single = Vehicle::factory()->withDriver($this->driver()[1])->create(['status' => 'active']);
        $this->roster($vehicle, $a);

        $html = $this->actingAs($this->manager())->get(route('maintenance-manager.duty-roster', ['vehicle_id' => $vehicle->id]))
            ->assertOk()->assertSee($a->name)->getContent();

        $this->assertStringNotContainsString($single->plate_number, $html, 'a one-driver vehicle has no roster');
    }

    public function test_generating_an_alternating_schedule()
    {
        $manager = $this->manager();
        [$vehicle, [, $a], [, $b]] = $this->shared();

        $this->actingAs($manager)->post(route('maintenance-manager.duty-roster.generate', $vehicle), [
            'start_date' => today()->toDateString(), 'first_driver_id' => $b->id, 'days' => 6,
        ])->assertSessionHas('success');

        $days = VehicleDutyDay::where('vehicle_id', $vehicle->id)->orderBy('duty_date')->pluck('driver_id')->all();
        $this->assertSame([$b->id, $a->id, $b->id, $a->id, $b->id, $a->id], $days);
        $this->assertSame($b->id, $vehicle->dutyDriverOn(today())->id);
        $this->assertSame($a->id, $vehicle->dutyDriverOn(today()->addDay())->id);
    }

    public function test_generating_replaces_what_the_days_held_and_is_validated()
    {
        $manager = $this->manager();
        [$vehicle, [, $a], [, $b]] = $this->shared();
        $this->roster($vehicle, $a);

        $this->actingAs($manager)->post(route('maintenance-manager.duty-roster.generate', $vehicle), [
            'start_date' => today()->toDateString(), 'first_driver_id' => $b->id, 'days' => 2,
        ]);
        $this->assertSame($b->id, $vehicle->dutyDriverOn(today())->id);

        $this->actingAs($manager)->post(route('maintenance-manager.duty-roster.generate', $vehicle), [
            'start_date' => today()->subDay()->toDateString(), 'first_driver_id' => $b->id, 'days' => 5,
        ])->assertSessionHasErrors('start_date');
        $this->actingAs($manager)->post(route('maintenance-manager.duty-roster.generate', $vehicle), [
            'start_date' => today()->toDateString(), 'first_driver_id' => 99999999, 'days' => 5,
        ])->assertSessionHasErrors('first_driver_id');
        $this->actingAs($manager)->post(route('maintenance-manager.duty-roster.generate', $vehicle), [
            'start_date' => today()->toDateString(), 'first_driver_id' => $b->id, 'days' => 500,
        ])->assertSessionHasErrors('days');
    }

    public function test_the_manager_swaps_and_clears_single_days_and_past_days_are_left_alone()
    {
        $manager = $this->manager();
        [$vehicle, [, $a], [, $b]] = $this->shared();
        $tomorrow = today()->addDay()->toDateString();
        $yesterday = today()->subDay()->toDateString();
        $this->roster($vehicle, $a);
        $this->roster($vehicle, $a, $tomorrow);
        $this->roster($vehicle, $a, $yesterday);

        $this->actingAs($manager)->put(route('maintenance-manager.duty-roster.update', $vehicle), ['days' => [
            today()->toDateString() => $b->id,   // swapped
            $tomorrow => '',                      // cleared
            $yesterday => $b->id,                 // history: ignored
            today()->addDays(2)->toDateString() => $a->id, // new line
        ]])->assertSessionHas('success');

        $this->assertSame($b->id, $vehicle->dutyDriverOn(today())->id);
        $this->assertNull($vehicle->dutyDriverOn($tomorrow));
        $this->assertSame($a->id, $vehicle->dutyDriverOn($yesterday)->id);
        $this->assertSame($a->id, $vehicle->dutyDriverOn(today()->addDays(2))->id);
    }

    public function test_a_day_can_only_go_to_one_of_the_vehicles_two_drivers()
    {
        [$vehicle] = $this->shared();
        [, $stranger] = $this->driver();

        $this->actingAs($this->manager())->put(route('maintenance-manager.duty-roster.update', $vehicle), ['days' => [
            today()->toDateString() => $stranger->id,
        ]])->assertSessionHasErrors('days.*');

        $this->assertSame(0, VehicleDutyDay::where('vehicle_id', $vehicle->id)->count());
    }

    public function test_a_roster_needs_two_drivers_on_the_vehicle()
    {
        [, $a] = $this->driver();
        $single = Vehicle::factory()->withDriver($a)->create(['status' => 'active']);

        $this->actingAs($this->manager())->post(route('maintenance-manager.duty-roster.generate', $single), [
            'start_date' => today()->toDateString(), 'first_driver_id' => $a->id, 'days' => 4,
        ])->assertStatus(422);
    }
}
