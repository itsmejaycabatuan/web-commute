<?php

namespace Tests\Feature\Nfr;

use App\Jobs\RetryBroadcastLocation;
use App\Models\Driver;
use App\Models\User;
use App\Models\VehicleLocation;
use App\Models\VehicleLocationHistory;
use App\Models\Wallet;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

/** Reliability and data integrity (docs/Non_Functional_Requirements.md §4). */
class ReliabilityNfrTest extends NfrTestCase
{
    private function broadcast(User $driver, int $vehicleId, array $extra = [])
    {
        return $this->actingAs($driver)->postJson('/track/vehicle/broadcast', $extra + [
            'vehicle_id' => (string) $vehicleId, 'latitude' => 10.3157, 'longitude' => 123.8854,
            'user_id' => $driver->id, 'timestamp' => 1700000000001,
        ]);
    }

    /**
     * @group NFR-REL-02
     * @group NFR-REL
     */
    public function test_an_identical_top_up_within_two_minutes_is_credited_only_once()
    {
        $user = $this->commuter(0);
        $topup = fn (int $amount) => $this->actingAs($user)->post(route('payment.topup.process'), ['amount' => $amount, 'payment-method' => 'gcash']);

        $topup(50)->assertSessionHas('success');
        $topup(50)->assertSessionHas('error');                 // double click / refresh / replay
        $this->assertEquals(50.0, (float) Wallet::where('user_id', $user->id)->value('balance'));

        $topup(60)->assertSessionHas('success');               // a different amount is a different top-up
        $this->assertEquals(110.0, (float) Wallet::where('user_id', $user->id)->value('balance'));
    }

    /**
     * @group NFR-REL-03
     * @group NFR-REL
     */
    public function test_a_driver_application_that_fails_part_way_leaves_no_user_no_driver_and_no_image()
    {
        Storage::fake('public');
        Driver::creating(function () {
            throw new \RuntimeException('forced failure after the user row was written');
        });

        $this->post(route('driver.register'), [
            'name' => 'Half Written', 'email' => 'half.written@example.com', 'contact_info' => '917 123 4567',
            'password' => 'password123', 'confirm-password' => 'password123', 'terms' => 'on',
            'license_image' => UploadedFile::fake()->image('licence.png', 600, 400)->size(200),
        ])->assertSessionHasErrors('email');

        $this->assertNull(User::where('email', 'half.written@example.com')->first(), 'the user row must roll back');
        $this->assertSame([], Storage::disk('public')->allFiles(), 'the stored licence image must be deleted again');
    }

    /**
     * @group NFR-REL-03
     * @group NFR-REL
     */
    public function test_a_commuter_registration_that_fails_creating_the_wallet_leaves_no_account()
    {
        Wallet::creating(function () {
            throw new \RuntimeException('forced wallet failure');
        });

        $this->post(route('users.register'), [
            'email' => 'no.wallet@example.com', 'password' => 'password123', 'confirm-password' => 'password123', 'terms' => 'on',
        ])->assertSessionHasErrors('email');

        $this->assertNull(User::where('email', 'no.wallet@example.com')->first());
    }

    /**
     * @group NFR-REL-05
     * @group NFR-REL
     */
    public function test_repeating_a_broadcast_with_the_same_timestamp_changes_nothing()
    {
        [$driver, , $vehicle] = $this->driverWithVehicle();
        $historyBefore = VehicleLocationHistory::count();

        $first = $this->broadcast($driver, $vehicle->id)->assertOk();
        $second = $this->broadcast($driver, $vehicle->id)->assertOk();

        $this->assertFalse($first->json('duplicate'));
        $this->assertTrue($second->json('duplicate'), 'the second send is recognised as a replay');
        $this->assertSame(1, VehicleLocation::where('vehicle_id', (string) $vehicle->id)->count());
        $this->assertSame($historyBefore + 1, VehicleLocationHistory::count(), 'one breadcrumb, not two');
    }

    /**
     * @group NFR-REL-05
     * @group NFR-REL
     */
    public function test_a_failed_write_answers_409_with_retry_after_and_queues_one_retry()
    {
        Bus::fake();
        [$driver, , $vehicle] = $this->driverWithVehicle();
        VehicleLocation::saving(function () {
            throw new \RuntimeException('database unavailable');
        });

        $response = $this->broadcast($driver, $vehicle->id, ['timestamp' => 1700000000002]);

        $response->assertStatus(409)->assertJson(['success' => false, 'retry' => true, 'retry_after' => 5]);
        $this->assertSame('5', $response->headers->get('Retry-After'));
        Bus::assertDispatched(RetryBroadcastLocation::class, 1);
    }

    /**
     * @group NFR-REL-08
     * @group NFR-REL
     */
    public function test_the_four_housekeeping_jobs_are_scheduled()
    {
        $this->assertSame(0, Artisan::call('schedule:list'));
        $listing = Artisan::output();

        foreach (['auth:clear-resets', 'vehicle:offline-check', 'payments:expire-pending', 'activitylog:clean'] as $command) {
            $this->assertStringContainsString($command, $listing, "{$command} is not on the scheduler");
        }
    }
}
