<?php

namespace Tests\Feature\Nfr;

use App\Events\LocationUpdated;
use App\Helpers\LocationPrivacy;
use App\Models\User;
use App\Models\VehicleLocation;
use Illuminate\Support\Facades\Artisan;
use Spatie\Activitylog\Models\Activity;

/** Privacy (docs/Non_Functional_Requirements.md §3). */
class PrivacyNfrTest extends NfrTestCase
{
    private const LAT = 10.3157;
    private const LNG = 123.8854;

    /**
     * @group NFR-PRIV-01
     * @group NFR-PRIV
     */
    public function test_an_obfuscated_position_is_always_within_200_metres_and_never_the_real_one()
    {
        $farthest = 0.0;
        $unchanged = 0;

        for ($i = 0; $i < 3000; $i++) {
            $p = LocationPrivacy::obfuscate(self::LAT, self::LNG);
            $d = $this->metres(self::LAT, self::LNG, $p['lat'], $p['lng']);
            $farthest = max($farthest, $d);
            $unchanged += $d < 1 ? 1 : 0;
            $this->assertSame(200, $p['privacy_radius']);
        }

        $this->assertLessThanOrEqual(201.0, $farthest, 'a point was published more than 200 m (+rounding) from the real position');
        $this->assertGreaterThan(100.0, $farthest, 'the offset is genuinely spread over the circle, not a token nudge');
        $this->assertLessThan(15, $unchanged, 'almost no sample may land within 1 m of the real position');
    }

    /**
     * @group NFR-PRIV-01
     * @group NFR-PRIV
     */
    public function test_the_live_feed_never_contains_the_exact_position_or_the_drivers_account()
    {
        [, , $vehicle] = $this->driverWithVehicle();
        VehicleLocation::create([
            'vehicle_id' => (string) $vehicle->id, 'user_id' => null,
            'latitude' => self::LAT, 'longitude' => self::LNG, 'last_update' => now(),
        ]);

        $entry = collect($this->getJson('/track/vehicles/active')->assertOk()->json('vehicles'))
            ->firstWhere('vehicle_id', (string) $vehicle->id);

        $this->assertNotNull($entry);
        $this->assertLessThanOrEqual(201.0, $this->metres(self::LAT, self::LNG, (float) $entry['lat'], (float) $entry['lng']));
        $this->assertNotEquals(self::LAT, (float) $entry['lat'], 'the exact latitude must not be published');
        $this->assertSame(200, $entry['privacy_radius']);
        $this->assertArrayNotHasKey('user_id', $entry);
    }

    /**
     * @group NFR-PRIV-01
     * @group NFR-PRIV
     */
    public function test_the_push_event_carries_an_obfuscated_position_not_the_real_one()
    {
        for ($i = 0; $i < 200; $i++) {
            $event = new LocationUpdated('1', self::LAT, self::LNG, 5, 3, 1);

            $this->assertLessThanOrEqual(201.0, $this->metres(self::LAT, self::LNG, $event->obfuscatedLat, $event->obfuscatedLng));
            $this->assertSame([$event->obfuscatedLng, $event->obfuscatedLat], $event->coordinates);
            $this->assertSame(200, $event->privacyRadius);
        }
        $this->assertFalse(property_exists(LocationUpdated::class, 'latitude'), 'the raw latitude must not be a property of the broadcast event');
    }

    /**
     * @group NFR-PRIV-02
     * @group NFR-PRIV
     */
    public function test_a_commuter_account_needs_nothing_but_an_email_and_a_password()
    {
        $this->post(route('users.register'), [
            'email' => 'minimal@example.com', 'password' => 'password123', 'confirm-password' => 'password123', 'terms' => 'on',
        ])->assertSessionHasNoErrors();

        $user = User::where('email', 'minimal@example.com')->firstOrFail();
        $this->assertNull($user->driver, 'no driver profile, no licence, no phone number');
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing('users');
        sort($columns);
        $this->assertSame(
            ['anonymised_at', 'created_at', 'email', 'email_verified_at', 'id', 'is_suspended', 'password', 'remember_token', 'suspended_at', 'updated_at'],
            $columns,
            'the users table holds no name, phone number or address — a new personal column must be a deliberate decision'
        );
    }

    /**
     * @group NFR-PRIV-07
     * @group NFR-PRIV
     */
    public function test_audit_entries_are_deleted_after_the_retention_period()
    {
        $this->assertSame(365, (int) config('activitylog.delete_records_older_than_days'), 'default retention');

        $old = activity()->log('retention test — old');
        $recent = activity()->log('retention test — recent');
        Activity::where('id', $old->id)->update(['created_at' => now()->subDays(400)]);
        Activity::where('id', $recent->id)->update(['created_at' => now()->subDays(10)]);

        $this->assertSame(0, Artisan::call('activitylog:clean'));

        $this->assertNull(Activity::find($old->id), 'an entry older than the retention period is deleted');
        $this->assertNotNull(Activity::find($recent->id), 'a recent one is kept');
    }
}
