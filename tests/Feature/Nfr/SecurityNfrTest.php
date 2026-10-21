<?php

namespace Tests\Feature\Nfr;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Driver;
use App\Models\TimeKeeping;
use App\Models\User;
use App\Models\VehicleLocation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/** Security (docs/Non_Functional_Requirements.md §2). */
class SecurityNfrTest extends NfrTestCase
{
    private function registrationPayload(array $overrides = []): array
    {
        return $overrides + [
            'email' => 'new.person@example.com', 'password' => 'password123',
            'confirm-password' => 'password123', 'terms' => 'on',
        ];
    }

    private function driverRegistration(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Test Driver', 'email' => 'drv.apply@example.com', 'contact_info' => '917 123 4567',
            'password' => 'password123', 'confirm-password' => 'password123', 'terms' => 'on',
            'license_image' => UploadedFile::fake()->image('licence.png', 600, 400)->size(200),
        ];
    }

    // ─────────────────────────────── NFR-SEC-01 ───────────────────────────────

    /**
     * @group NFR-SEC-01
     * @group NFR-SEC
     */
    public function test_passwords_are_stored_only_as_bcrypt_hashes()
    {
        $this->post(route('users.register'), $this->registrationPayload(['email' => 'hash.me@example.com']));

        $stored = User::where('email', 'hash.me@example.com')->value('password');

        $this->assertNotSame('password123', $stored);
        $this->assertStringStartsWith('$2y$', $stored, 'bcrypt');
        $this->assertTrue(Hash::check('password123', $stored));
    }

    /**
     * @group NFR-SEC-01
     * @group NFR-SEC
     */
    public function test_a_password_under_eight_characters_is_refused_everywhere_one_can_be_set()
    {
        // registration
        $this->post(route('users.register'), $this->registrationPayload(['password' => 'short12', 'confirm-password' => 'short12']))
            ->assertSessionHasErrors('password');

        // change in settings
        $user = $this->commuter();
        $this->actingAs($user)->put(route('settings.password'), [
            'current_password' => '123456789', 'password' => 'short12', 'password_confirmation' => 'short12',
        ])->assertSessionHasErrors('password');

        // admin creating staff
        $this->actingAs($this->userWithRole('admin'))->post(route('staff.store'), [
            'email' => 'weak.staff@example.com', 'role' => 'driver_manager', 'password' => 'short12', 'password_confirmation' => 'short12',
        ])->assertSessionHasErrors('password');

        // reset link
        auth()->logout();
        $this->post(route('password.update'), [
            'token' => 'x', 'email' => 'someone@example.com', 'password' => 'short12', 'confirm-password' => 'short12',
        ])->assertSessionHasErrors('password');
    }

    // ─────────────────────────────── NFR-SEC-02 ───────────────────────────────

    /**
     * @group NFR-SEC-02
     * @group NFR-SEC
     */
    public function test_signing_in_starts_a_fresh_session()
    {
        $user = $this->commuter();
        $this->startSession();
        $before = session()->getId();

        $this->post(route('users.login'), ['email' => $user->email, 'password' => '123456789', '_token' => csrf_token()]);

        $this->assertNotSame($before, session()->getId(), 'the session id must change at sign-in (session-fixation guard)');
    }

    // ─────────────────────────────── NFR-SEC-03 ───────────────────────────────

    /**
     * @group NFR-SEC-03
     * @group NFR-SEC
     */
    public function test_sign_in_is_limited_to_five_attempts_a_minute()
    {
        $user = $this->commuter();

        for ($i = 1; $i <= 5; $i++) {
            $this->post(route('users.login'), ['email' => $user->email, 'password' => "wrong-pw-{$i}"])->assertStatus(302);
        }

        $this->post(route('users.login'), ['email' => $user->email, 'password' => '123456789'])
            ->assertStatus(429);
    }

    /**
     * @group NFR-SEC-03
     * @group NFR-SEC
     */
    public function test_registration_is_limited_to_six_attempts_a_minute()
    {
        for ($i = 1; $i <= 6; $i++) {
            $this->post(route('users.register'), ['email' => 'not-an-email'])->assertStatus(302);
        }

        $this->post(route('users.register'), ['email' => 'not-an-email'])->assertStatus(429);
    }

    /**
     * @group NFR-SEC-03
     * @group NFR-SEC
     */
    public function test_driver_registration_is_limited_to_six_attempts_a_minute()
    {
        for ($i = 1; $i <= 6; $i++) {
            $this->post(route('driver.register'), ['email' => 'not-an-email'])->assertStatus(302);
        }

        $this->post(route('driver.register'), ['email' => 'not-an-email'])->assertStatus(429);
    }

    /**
     * @group NFR-SEC-03
     * @group NFR-SEC
     */
    public function test_top_ups_are_limited_to_five_requests_a_minute()
    {
        $user = $this->commuter();

        foreach ([10, 11, 12, 13, 14] as $amount) {
            $this->actingAs($user)->post(route('payment.topup.process'), ['amount' => $amount, 'payment-method' => 'gcash'])
                ->assertStatus(302);
        }

        $this->actingAs($user)->post(route('payment.topup.process'), ['amount' => 15, 'payment-method' => 'gcash'])
            ->assertStatus(429);
    }

    /**
     * @group NFR-SEC-03
     * @group NFR-SEC
     */
    public function test_the_verification_email_can_be_requested_at_most_six_times_a_minute()
    {
        Notification::fake();
        $user = User::factory()->create(['email_verified_at' => null])->assignRole('commuter');

        for ($i = 1; $i <= 6; $i++) {
            $this->actingAs($user)->post(route('verification.send'))->assertStatus(302);
        }

        $this->actingAs($user)->post(route('verification.send'))->assertStatus(429);
    }

    // ─────────────────────────────── NFR-SEC-04 ───────────────────────────────

    /** Run the app with real CSRF verification (Laravel switches it off while unit testing). */
    private function strictCsrf(): void
    {
        $this->app->bind(VerifyCsrfToken::class, StrictCsrfToken::class);
    }

    /**
     * @group NFR-SEC-04
     * @group NFR-SEC
     */
    public function test_a_state_changing_request_without_a_csrf_token_is_refused()
    {
        $this->strictCsrf();
        $user = $this->commuter();

        $this->actingAs($user)->post(route('users.logout'))->assertStatus(419);
        $this->actingAs($user)->delete(route('users.delete-account'))->assertStatus(419);
        $this->actingAs($this->userWithRole('admin'))->post(route('staff.store'), ['email' => 'x@example.com'])->assertStatus(419);
        $this->post(route('users.login'), ['email' => $user->email, 'password' => '123456789'])->assertStatus(419);
        $this->assertNotNull(User::find($user->id), 'nothing happened');
    }

    /**
     * @group NFR-SEC-04
     * @group NFR-SEC
     */
    public function test_the_same_request_with_a_valid_token_is_accepted()
    {
        $this->strictCsrf();
        $user = $this->commuter();

        $this->actingAs($user)->withSession(['_token' => 'csrf-ok'])
            ->post(route('users.logout'), ['_token' => 'csrf-ok'])
            ->assertRedirect(route('login'));
    }

    /**
     * @group NFR-SEC-04
     * @group NFR-SEC
     */
    public function test_the_only_csrf_exemptions_are_the_signed_webhook_and_the_driver_location_feed()
    {
        $this->strictCsrf();

        // 401 = reached the signature check; 403 = reached the controller's own authorisation. Neither is 419.
        $this->postJson('/webhooks/paymongo', ['data' => []])->assertStatus(401);
        $this->postJson('/track/vehicle/broadcast', ['vehicle_id' => '1', 'latitude' => 1, 'longitude' => 1, 'user_id' => 1])
            ->assertStatus(403);

        $exempt = (new \ReflectionClass(VerifyCsrfToken::class))->getDefaultProperties()['except'];
        $routes = collect(\Route::getRoutes())->filter(fn ($r) => array_intersect(['POST', 'PUT', 'PATCH', 'DELETE'], $r->methods()));
        $exposed = $routes->filter(fn ($r) => collect($exempt)->contains(fn ($pattern) => \Illuminate\Support\Str::is($pattern, $r->uri())))
            ->map(fn ($r) => $r->uri())->values()->all();

        sort($exposed);
        $this->assertSame(['track/vehicle/broadcast', 'webhooks/paymongo'], $exposed,
            'a state-changing route is exempt from CSRF that this requirement does not allow');
    }

    // ─────────────────────────────── NFR-SEC-07 ───────────────────────────────

    /**
     * @group NFR-SEC-07
     * @group NFR-SEC
     */
    public function test_a_driver_cannot_broadcast_a_position_for_another_drivers_vehicle()
    {
        [$mine, , ] = $this->driverWithVehicle();
        [, , $theirVehicle] = $this->driverWithVehicle();

        $this->actingAs($mine)->postJson('/track/vehicle/broadcast', [
            'vehicle_id' => (string) $theirVehicle->id, 'latitude' => 10.3, 'longitude' => 123.9, 'user_id' => $mine->id,
        ])->assertStatus(403);

        $this->assertSame(0, VehicleLocation::where('vehicle_id', (string) $theirVehicle->id)->count());
    }

    // ─────────────────────────────── NFR-SEC-09 ───────────────────────────────

    /**
     * @group NFR-SEC-09
     * @group NFR-SEC
     */
    public function test_worked_hours_come_from_the_clock_times_not_from_the_request()
    {
        $manager = $this->userWithRole('driver_manager');
        $driver = Driver::factory()->approved()->create();

        $this->actingAs($manager)->post(route('driver-manager.time-keeping.store'), [
            'driver_id' => $driver->id, 'date' => now()->toDateString(),
            'time_in' => '08:00', 'time_out' => '18:00',
            'hours_worked' => 99, 'overtime_hours' => 0,   // a forged claim
        ])->assertSessionHas('success');

        $entry = TimeKeeping::where('driver_id', $driver->id)->firstOrFail();
        $this->assertEquals(10.0, (float) $entry->hours_worked);
        $this->assertEquals(2.0, (float) $entry->overtime_hours);
    }

    // ─────────────────────────────── NFR-SEC-10 ───────────────────────────────

    /**
     * @group NFR-SEC-10
     * @group NFR-SEC
     */
    public function test_sign_in_gives_the_same_answer_for_an_unknown_email_and_a_wrong_password()
    {
        $user = $this->commuter();

        $this->post(route('users.login'), ['email' => 'nobody@example.com', 'password' => 'whatever123'])
            ->assertSessionHasErrors('credentials');
        $forUnknownEmail = session('errors')->first('credentials');

        $this->post(route('users.login'), ['email' => $user->email, 'password' => 'whatever123'])
            ->assertSessionHasErrors('credentials');
        $forWrongPassword = session('errors')->first('credentials');

        $this->assertNotEmpty($forUnknownEmail);
        $this->assertSame($forUnknownEmail, $forWrongPassword, 'the message must not reveal which half was wrong');
    }

    // ─────────────────────────────── NFR-SEC-11 ───────────────────────────────

    /**
     * @group NFR-SEC-11
     * @group NFR-SEC
     */
    public function test_a_licence_upload_must_be_a_small_jpg_or_png()
    {
        Storage::fake('public');

        $this->post(route('driver.register'), $this->driverRegistration([
            'license_image' => UploadedFile::fake()->create('licence.pdf', 100, 'application/pdf'),
        ]))->assertSessionHasErrors('license_image');

        $this->post(route('driver.register'), $this->driverRegistration([
            'email' => 'big.file@example.com',
            'license_image' => UploadedFile::fake()->image('big.png', 800, 600)->size(3000),
        ]))->assertSessionHasErrors('license_image');

        $this->assertNull(User::where('email', 'big.file@example.com')->first());

        $this->post(route('driver.register'), $this->driverRegistration(['email' => 'good.file@example.com']))
            ->assertSessionHasNoErrors();
        $this->assertNotNull(User::where('email', 'good.file@example.com')->first());
    }

    // ─────────────────────────────── NFR-SEC-14 ───────────────────────────────

    /**
     * @group NFR-SEC-14
     * @group NFR-SEC
     */
    public function test_every_destructive_action_sits_behind_a_confirmation_dialog()
    {
        $admin = $this->userWithRole('admin');
        $this->actingAs($admin);

        $this->get(route('commuters.index'))->assertSee('Delete Commuter?');
        $this->get(route('staff.index'))->assertSee('Delete staff account?');
        $this->get(route('drivers.index'))->assertSee('Delete Driver?');
        $this->get(route('vehicles.index'))->assertSee('This will permanently remove')->assertSee('This action cannot be undone');

        $this->actingAs($this->userWithRole('driver_manager'))->get(route('driver-manager.violation-codes'))->assertSee('Delete Code?');
        $this->actingAs($this->userWithRole('maintenance_manager'))->get(route('maintenance-manager.maintenance-tasks'))->assertSee('Delete Task?');
    }
}

/** Real CSRF verification: Laravel skips it in unit tests, which would make the CSRF tests meaningless. */
class StrictCsrfToken extends VerifyCsrfToken
{
    protected function runningUnitTests()
    {
        return false;
    }
}
