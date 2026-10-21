<?php

namespace Tests\Feature\Nfr;

use App\Models\Driver;
use App\Models\Payment;
use App\Models\TimeKeeping;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

/**
 * KNOWN GAPS — each test asserts the behaviour a non-functional requirement WANTS and the system does not
 * yet have, so each one FAILS today. They are tagged `nfr-gap` and excluded from the default run
 * (phpunit.xml) so the suite stays green; run them on purpose to see the backlog:
 *
 *     php vendor/bin/phpunit --group nfr-gap
 *
 * When a gap is fixed its test starts passing — then remove the `nfr-gap` tag and it joins the normal suite.
 * docs/Non_Functional_Requirements.md lists each one with its result.
 *
 * @group nfr-gap
 */
class KnownGapsNfrTest extends NfrTestCase
{
    /**
     * @group NFR-SEC-17
     * @group NFR-SEC
     */
    public function test_gap_the_password_reset_request_is_rate_limited()
    {
        for ($i = 1; $i <= 8; $i++) {
            $last = $this->post(route('password.email'), ['email' => 'someone@example.com']);
        }

        $last->assertStatus(429);
    }

    /**
     * @group NFR-PERF-02
     * @group NFR-SEC-17
     * @group NFR-SCL-03
     * @group NFR-PERF
     * @group NFR-SEC
     * @group NFR-SCL
     */
    public function test_gap_the_location_broadcast_endpoint_is_rate_limited_on_the_server()
    {
        [$driver, , $vehicle] = $this->driverWithVehicle();
        $limited = false;

        for ($i = 0; $i < 40 && ! $limited; $i++) {
            $limited = $this->actingAs($driver)->postJson('/track/vehicle/broadcast', [
                'vehicle_id' => (string) $vehicle->id, 'latitude' => 10.3, 'longitude' => 123.9,
                'user_id' => $driver->id, 'timestamp' => 1700000100000 + $i,
            ])->getStatusCode() === 429;
        }

        $this->assertTrue($limited, '40 position posts in a row were all accepted: a modified client can flood the endpoint');
    }

    /**
     * @group NFR-SEC-19
     * @group NFR-SEC
     */
    public function test_gap_the_password_reset_form_does_not_reveal_whether_an_email_is_registered()
    {
        $user = $this->commuter();

        $known = $this->from('/forgot-password')->post(route('password.email'), ['email' => $user->email]);
        $knownErrors = session('errors') ? session('errors')->all() : [];
        $unknown = $this->from('/forgot-password')->post(route('password.email'), ['email' => 'nobody.at.all@example.com']);
        $unknownErrors = session('errors') ? session('errors')->all() : [];

        $this->assertSame($knownErrors === [], $unknownErrors === [],
            'a registered email gets a success message and an unregistered one an error, so the form can be used to find out who has an account');
    }

    /**
     * @group NFR-SEC-15
     * @group NFR-SEC
     */
    public function test_gap_signing_out_other_devices_also_revokes_api_tokens()
    {
        $user = $this->commuter();
        $user->createToken('phone');
        $this->assertSame(1, $user->tokens()->count());

        $this->actingAs($user)->put(route('settings.password'), [
            'current_password' => '123456789', 'password' => 'a-brand-new-pw', 'password_confirmation' => 'a-brand-new-pw',
        ])->assertSessionHas('success');

        $this->assertSame(0, $user->fresh()->tokens()->count(), 'a Sanctum token outlived the password it was issued under');
    }

    /**
     * @group NFR-SEC-20
     * @group NFR-SEC
     */
    public function test_gap_the_report_csv_neutralises_spreadsheet_formulas()
    {
        $payer = User::factory()->create(['email' => '=cmd@example.com'])->assignRole('commuter');
        Payment::factory()->create(['paid_by' => $payer->id, 'status' => 'paid', 'paid_at' => now()]);

        $csv = $this->actingAs($this->userWithRole('admin'))
            ->get(route('reports.export', ['type' => 'financial', 'start_date' => now()->subDay()->toDateString(), 'end_date' => now()->addDay()->toDateString()]))
            ->streamedContent();

        $this->assertStringNotContainsString(',=cmd@example.com', $csv, 'a cell that starts with "=" would run as a formula when the file is opened');
    }

    /**
     * @group NFR-PRIV-03
     * @group NFR-PRIV
     */
    public function test_gap_driver_licence_images_are_not_stored_on_the_public_disk()
    {
        Storage::fake('public');

        $this->post(route('driver.register'), [
            'name' => 'Private Licence', 'email' => 'private.licence@example.com', 'contact_info' => '917 123 4567',
            'password' => 'password123', 'confirm-password' => 'password123', 'terms' => 'on',
            'license_image' => UploadedFile::fake()->image('licence.png', 600, 400)->size(200),
        ])->assertSessionHasNoErrors();

        $this->assertSame([], Storage::disk('public')->allFiles('licenses'),
            'the licence image sits on the public disk: anyone who learns its path can fetch it');
    }

    /**
     * @group NFR-PRIV-05
     * @group NFR-PRIV
     */
    public function test_gap_the_terms_and_privacy_links_on_the_registration_form_lead_somewhere()
    {
        $html = $this->get(route('register'))->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression('/<a[^>]*href="#"[^>]*>\s*(Terms of Service|Privacy Policy)/i', $html,
            'both links are placeholders (href="#"): users accept terms they cannot read');
    }

    /**
     * @group NFR-REL-10
     * @group NFR-REL
     */
    public function test_gap_a_clock_in_that_cannot_set_the_driver_status_leaves_no_shift_behind()
    {
        [$driver, $profile] = $this->driverWithVehicle();
        Driver::updating(function () {
            throw new \RuntimeException('forced failure of the status write');
        });

        $this->actingAs($driver)->post(route('driver.timekeeping.clock-in'));

        $this->assertSame(0, TimeKeeping::where('driver_id', $profile->id)->count(),
            'the shift record was written and the status was not: the driver has a shift but stays offline');
    }

    /**
     * @group NFR-REL-11
     * @group NFR-REL
     */
    public function test_gap_a_database_failure_while_deleting_your_account_gives_a_friendly_message()
    {
        $user = $this->commuter();
        User::deleting(function () {
            throw new \RuntimeException('forced failure while deleting');
        });

        $response = $this->actingAs($user)->delete(route('users.delete-account'));

        $this->assertNotSame(500, $response->getStatusCode(), 'the default error page was shown');
        $response->assertSessionHas('error');
    }

    /**
     * @group NFR-SCL-01
     * @group NFR-SCL
     */
    public function test_gap_the_timekeeping_ledger_is_paged_by_the_server()
    {
        $driver = Driver::factory()->approved()->create();
        TimeKeeping::factory()->count(25)->create(['driver_id' => $driver->id]);

        $entries = $this->actingAs($this->userWithRole('driver_manager'))
            ->get(route('driver-manager.time-keeping'))->assertOk()->viewData('entries');

        $this->assertLessThanOrEqual(10, count($entries),
            'the page loads up to 1,000 entries and pages them in the browser, so the whole ledger is read on every visit');
    }

    /**
     * @group NFR-SCL-02
     * @group NFR-SCL
     */
    public function test_gap_location_history_has_a_retention_job()
    {
        Artisan::call('schedule:list');

        $this->assertMatchesRegularExpression('/location|history/i', Artisan::output(),
            'vehicle_location_histories gains a row for every accepted broadcast and nothing ever removes them');
    }
}
