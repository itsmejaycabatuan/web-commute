<?php

namespace Tests\Feature\Nfr;

use App\Models\Payment;
use App\Models\VehicleLocation;
use App\Services\PayMongoService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Performance and responsiveness (docs/Non_Functional_Requirements.md §1).
 * Timing that only a browser can measure (the 2-second broadcast throttle, push latency) is a manual check.
 */
class PerformanceNfrTest extends NfrTestCase
{
    /** VehicleLocationFactory is stale (it sets a driver_id column the table no longer has), so build rows directly. */
    private function position(array $attributes): VehicleLocation
    {
        return VehicleLocation::create($attributes + ['latitude' => 10.3157, 'longitude' => 123.8854]);
    }

    /**
     * @group NFR-PERF-03
     * @group NFR-PERF
     */
    public function test_the_live_feed_only_lists_vehicles_that_reported_in_the_last_five_minutes()
    {
        [, , $fresh] = $this->driverWithVehicle();
        [, , $stale] = $this->driverWithVehicle();
        $this->position(['vehicle_id' => (string) $fresh->id, 'last_update' => now()->subMinutes(4)]);
        $this->position(['vehicle_id' => (string) $stale->id, 'last_update' => now()->subMinutes(6)]);

        $ids = collect($this->getJson('/track/vehicles/active')->assertOk()->json('vehicles'))
            ->pluck('vehicle_id')->map(fn ($id) => (int) $id);

        $this->assertTrue($ids->contains($fresh->id), 'a vehicle that reported 4 minutes ago is live');
        $this->assertFalse($ids->contains($stale->id), 'a vehicle silent for 6 minutes is not');
    }

    /**
     * @group NFR-PERF-03
     * @group NFR-PERF
     */
    public function test_the_offline_check_flips_the_active_flag_after_five_minutes_of_silence()
    {
        [, , $silent] = $this->driverWithVehicle(['is_active' => true]);
        [, , $returning] = $this->driverWithVehicle(['is_active' => false]);
        $this->position(['vehicle_id' => (string) $silent->id, 'last_update' => now()->subMinutes(10)]);
        $this->position(['vehicle_id' => (string) $returning->id, 'last_update' => now()->subMinute()]);

        $this->assertSame(0, Artisan::call('vehicle:offline-check'));

        $this->assertFalse((bool) $silent->fresh()->is_active, 'ten minutes of silence marks the vehicle inactive');
        $this->assertTrue((bool) $returning->fresh()->is_active, 'a fresh broadcast marks it active again');
    }

    /**
     * @group NFR-PERF-05
     * @group NFR-PERF
     */
    public function test_the_pending_payment_screen_asks_the_gateway_at_most_once_every_four_seconds()
    {
        config([
            'paymongo.enabled' => true, 'paymongo.secret_key' => 'sk_test_perf', 'paymongo.public_key' => 'pk_test_perf',
            'paymongo.base_url' => 'https://api.paymongo.com/v1', 'paymongo.currency' => 'PHP', 'paymongo.minor_unit_factor' => 100,
        ]);
        PayMongoService::flushConfigCache();

        $user = $this->commuter(0);
        Payment::factory()->create([
            'paid_by' => $user->id, 'transaction_id' => '#SC-PERF05', 'status' => 'pending',
            'payment_method' => 'GCash', 'paymongo_payment_intent_id' => 'pi_perf_05', 'paid_at' => null,
        ]);
        Http::fake(['https://api.paymongo.com/v1/payment_intents/pi_perf_05' => Http::response([
            'data' => ['id' => 'pi_perf_05', 'type' => 'payment_intent', 'attributes' => ['status' => 'awaiting_payment']],
        ])]);

        $this->actingAs($user);
        $this->get(route('payment.returned', ['ref' => '#SC-PERF05']))->assertOk();   // the page polls: first look asks the gateway…
        $this->get(route('payment.returned', ['ref' => '#SC-PERF05']))->assertOk();   // …the next ones, straight after, do not
        $this->get(route('payment.returned', ['ref' => '#SC-PERF05']))->assertOk();

        Http::assertSentCount(1);
    }

    /**
     * @group NFR-PERF-06
     * @group NFR-PERF
     */
    public function test_a_report_whose_queries_fail_is_cancelled_with_a_clear_message_instead_of_hanging_or_half_rendering()
    {
        DB::beforeExecuting(function (string $query) {
            if (str_contains($query, '`payments`')) {
                throw new \RuntimeException('simulated: the database is too slow');
            }
        });

        $this->actingAs($this->userWithRole('admin'))
            ->get(route('reports.generate', ['type' => 'financial', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31']))
            ->assertRedirect()
            ->assertSessionHas('error', 'The database is taking too long to generate this report. Please try a shorter date range.');
    }
}
