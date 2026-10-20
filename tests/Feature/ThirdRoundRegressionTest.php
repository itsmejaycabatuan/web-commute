<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Driver;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ThirdRoundRegressionTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'commuter', 'maintenance_manager'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    protected function userWithRole(string $role): User
    {
        return User::factory()->create(['email_verified_at' => now()])->assignRole($role);
    }

    protected function pending(User $user, int $hoursOld): Payment
    {
        return Payment::factory()->create([
            'paid_by' => $user->id, 'status' => 'pending', 'paid_at' => null,
            'created_at' => now()->subHours($hoursOld), 'updated_at' => now()->subHours($hoursOld),
        ]);
    }

    // ───────────── 1. pending payments expire after 12 hours ─────────────

    public function test_a_pending_payment_older_than_twelve_hours_is_closed()
    {
        $commuter = $this->userWithRole('commuter');
        $old = $this->pending($commuter, 13);
        $fresh = $this->pending($commuter, 11);

        Payment::expireStale();

        $this->assertSame('cancelled', $old->fresh()->status);
        $this->assertStringContainsString('12 hours', $old->fresh()->failure_message);
        $this->assertNotNull($old->fresh()->failed_at);
        $this->assertSame('pending', $fresh->fresh()->status);
    }

    public function test_the_expiry_never_touches_settled_or_failed_payments()
    {
        $commuter = $this->userWithRole('commuter');
        $paid = Payment::factory()->create(['paid_by' => $commuter->id, 'status' => 'paid', 'paid_at' => now()->subDays(3), 'created_at' => now()->subDays(3)]);
        $failed = Payment::factory()->create(['paid_by' => $commuter->id, 'status' => 'failed', 'paid_at' => null, 'created_at' => now()->subDays(3)]);

        Payment::expireStale();

        $this->assertSame('paid', $paid->fresh()->status);
        $this->assertSame('failed', $failed->fresh()->status);
    }

    public function test_opening_the_history_or_the_ledger_expires_stale_payments_without_a_scheduler()
    {
        $commuter = $this->userWithRole('commuter');
        $stale = $this->pending($commuter, 20);

        $this->actingAs($commuter)->get(route('payment.history'))->assertOk();
        $this->assertSame('cancelled', $stale->fresh()->status);

        $second = $this->pending($commuter, 30);
        $this->actingAs($this->userWithRole('admin'))->get(route('faretransactions'))->assertOk();
        $this->assertSame('cancelled', $second->fresh()->status);
    }

    public function test_returning_to_an_expired_payment_does_not_show_a_spinner()
    {
        $commuter = $this->userWithRole('commuter');
        $stale = $this->pending($commuter, 15);

        $this->actingAs($commuter)
            ->get(route('payment.returned', ['ref' => $stale->transaction_id]))
            ->assertRedirect(route('payment.history'));
    }

    public function test_the_expiry_command_is_scheduled_and_runnable()
    {
        $commuter = $this->userWithRole('commuter');
        $stale = $this->pending($commuter, 14);

        $this->artisan('payments:expire-pending')->assertExitCode(0);

        $this->assertSame('cancelled', $stale->fresh()->status);
    }

    // ───────────── 2. only free, eligible drivers can be assigned ─────────────

    public function test_the_vehicle_page_lists_only_approved_unsuspended_drivers()
    {
        $manager = $this->userWithRole('maintenance_manager');
        $ok = Driver::factory()->approved()->create(['name' => 'Eligible Edna']);
        $pending = Driver::factory()->pending()->create(['name' => 'Pending Pete']);
        $suspended = Driver::factory()->approved()->create(['name' => 'Suspended Sam']);
        $suspended->suspend();

        $ids = $this->actingAs($manager)->get(route('vehicles.index'))->assertOk()
            ->viewData('drivers')->pluck('id');

        $this->assertTrue($ids->contains($ok->id));
        $this->assertFalse($ids->contains($pending->id));
        $this->assertFalse($ids->contains($suspended->id));
    }

    public function test_the_driver_picker_hides_drivers_who_already_hold_a_vehicle()
    {
        $manager = $this->userWithRole('maintenance_manager');

        $this->actingAs($manager)->get(route('vehicles.index'))->assertOk()
            ->assertSee('availableDrivers', false)
            ->assertSee('d in availableDrivers', false);
    }

    public function test_a_driver_cannot_be_given_a_second_vehicle()
    {
        $manager = $this->userWithRole('maintenance_manager');
        $driver = Driver::factory()->approved()->create();
        Vehicle::factory()->create(['status' => 'active', 'driver_id' => $driver->id]);

        $this->actingAs($manager)->postJson(route('vehicles.store'), [
            'year' => 2022, 'brand' => 'Toyota', 'model' => 'Hiace', 'plate_number' => 'DUP-'.random_int(1000, 9999),
            'status' => 'active', 'acquisition_date' => '2022-01-01', 'driver_id' => $driver->id,
        ])->assertStatus(422)->assertJsonValidationErrors('driver_id');
    }

    public function test_a_vehicle_keeps_its_own_driver_when_edited()
    {
        $manager = $this->userWithRole('maintenance_manager');
        $driver = Driver::factory()->approved()->create();
        $vehicle = Vehicle::factory()->create(['status' => 'active', 'driver_id' => $driver->id]);

        $this->actingAs($manager)->patchJson(route('vehicles.update', $vehicle), [
            'year' => 2022, 'brand' => 'Toyota', 'model' => 'Hiace', 'plate_number' => $vehicle->plate_number,
            'status' => 'active', 'acquisition_date' => '2022-01-01', 'driver_id' => $driver->id,
        ])->assertOk();

        $this->assertSame($driver->id, $vehicle->fresh()->driver_id);
    }

    public function test_a_pending_or_suspended_driver_cannot_be_assigned_by_force()
    {
        $manager = $this->userWithRole('maintenance_manager');
        $pending = Driver::factory()->pending()->create();

        $this->actingAs($manager)->postJson(route('vehicles.store'), [
            'year' => 2022, 'brand' => 'Toyota', 'model' => 'Hiace', 'plate_number' => 'PND-'.random_int(1000, 9999),
            'status' => 'active', 'acquisition_date' => '2022-01-01', 'driver_id' => $pending->id,
        ])->assertStatus(422)->assertJsonValidationErrors('driver_id');
    }

    // ───────────── 3 + 4. select design and the working indicator ─────────────

    public function test_every_page_gets_one_select_design()
    {
        $css = view('components.toast', ['errors' => new \Illuminate\Support\ViewErrorBag])->render();

        $this->assertStringContainsString('select:not([multiple]):not([size])', $css);
        $this->assertStringContainsString('appearance: none !important', $css);
        $this->assertStringContainsString('.dark select option', $css);
    }

    public function test_ajax_forms_show_a_spinner_and_the_overlay_while_they_save()
    {
        $js = view('components.toast', ['errors' => new \Illuminate\Support\ViewErrorBag])->render();

        $this->assertStringContainsString('AppLoading.button(submitter, true)', $js);
        $this->assertStringContainsString("AppLoading.show(form.getAttribute('data-loading-text')", $js);
    }
}
