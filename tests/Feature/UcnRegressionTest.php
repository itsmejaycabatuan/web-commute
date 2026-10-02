<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Fare;
use App\Models\FareRate;
use App\Models\Payment;
use App\Models\TimeKeeping;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Regression coverage for the fixes made while reconciling UCN_SC_E006 / E010
 * with the implementation.
 */
class UcnRegressionTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'commuter']);
        Role::firstOrCreate(['name' => 'driver']);

        // Deterministic rate table so fare re-pricing is predictable:
        // 5.2 km falls in the 5 km tier => ceil(17.25) = PHP 18.
        $fare = Fare::factory()->create();
        FareRate::factory()->create(['fare_id' => $fare->id, 'km' => 1, 'regular' => 15, 'discount' => 12]);
        FareRate::factory()->create(['fare_id' => $fare->id, 'km' => 5, 'regular' => 17.25, 'discount' => 13.75]);
    }

    protected function commuter(float $balance = 500): User
    {
        $user = User::factory()->create()->assignRole('commuter');
        $user->markEmailAsVerified();
        Wallet::factory()->create(['user_id' => $user->id, 'balance' => (string) $balance]);

        return $user->fresh();
    }

    protected function driver(string $status = 'inactive'): array
    {
        $user = User::factory()->create()->assignRole('driver');
        $user->markEmailAsVerified();
        $driver = Driver::factory()->create(['user_id' => $user->id, 'status' => $status]);

        return [$user, $driver];
    }

    protected function balance(User $user): float
    {
        return (float) Wallet::where('user_id', $user->id)->value('balance');
    }

    protected function farePayload(array $overrides = []): array
    {
        return array_merge([
            'pickup' => 'Minglanilla',
            'destination' => 'IT Park',
            'distance' => '5.2',
            'amount' => 50,
            'payment-method' => 'Wallet',
            'transaction-id' => '#SC-TEST0001',
        ], $overrides);
    }

    /** E006 — a receipt must only be readable by the commuter who paid it. */
    public function test_receipt_cannot_be_read_by_another_commuter()
    {
        [$owner] = [$this->commuter()];
        [$stranger] = [$this->commuter()];

        $payment = Payment::factory()->create(['paid_by' => $owner->id]);

        $this->actingAs($owner)
            ->get(route('payment.showReceipt', $payment->id))
            ->assertOk();

        $this->actingAs($stranger)
            ->get(route('payment.showReceipt', $payment->id))
            ->assertNotFound();
    }

    /** E006 — the fare is re-read from storage via POST/redirect/GET. */
    public function test_paying_a_fare_redirects_to_the_stored_receipt()
    {
        $user = $this->commuter(500);

        $response = $this->actingAs($user)
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('payment.process'), $this->farePayload());

        $payment = Payment::where('transaction_id', '#SC-TEST0001')->firstOrFail();

        $response->assertRedirect(route('payment.showReceipt', $payment->id));
        // The fare is priced server-side from the 5 km tier (PHP 18), NOT from
        // the ₱50 posted by the browser.
        $this->assertEquals(18.0, (float) $payment->price);
        $this->assertEquals(482.0, (float) Wallet::where('user_id', $user->id)->value('balance'));
    }

    /** E006 — E1 payment method unavailable. */
    public function test_unknown_payment_method_is_rejected()
    {
        $user = $this->commuter(500);

        $this->actingAs($user)
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('payment.process'), $this->farePayload(['payment-method' => 'Bitcoin']))
            ->assertSessionHasErrors('payment-method');

        $this->assertSame(0, Payment::where('paid_by', $user->id)->count());
        $this->assertEquals(500.0, (float) Wallet::where('user_id', $user->id)->value('balance'));
    }

    /** E006 — E3 duplicate submission with the same transaction id. */
    public function test_duplicate_fare_submission_is_rejected()
    {
        $user = $this->commuter(500);

        $this->actingAs($user)->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('payment.process'), $this->farePayload());

        $this->actingAs($user)->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('payment.process'), $this->farePayload())
            ->assertSessionHas('error');

        $this->assertSame(1, Payment::where('paid_by', $user->id)->count());
        $this->assertEquals(482.0, (float) Wallet::where('user_id', $user->id)->value('balance'));
    }

    /** E006 — E2 insufficient balance leaves the wallet untouched. */
    public function test_wallet_payment_is_rejected_when_balance_is_insufficient()
    {
        $user = $this->commuter(10);

        $this->actingAs($user)->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('payment.process'), $this->farePayload())
            ->assertSessionHas('error');

        $this->assertSame(0, Payment::where('paid_by', $user->id)->count());
        $this->assertEquals(10.0, $this->balance($user));
    }

    /** E006 — a GCash fare with the gateway disabled is recorded as declared. */
    public function test_gcash_payment_without_the_gateway_does_not_debit_the_wallet()
    {
        $user = $this->commuter(500);

        $this->actingAs($user)->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('payment.process'), $this->farePayload(['payment-method' => 'GCash']));

        $this->assertSame(1, Payment::where('paid_by', $user->id)->where('payment_method', 'GCash')->count());
        $this->assertEquals(500.0, $this->balance($user));
    }

    /** E010 — clocking out takes the driver offline. */
    public function test_clock_out_sets_driver_status_to_inactive()
    {
        [$user, $driver] = $this->driver('active');

        TimeKeeping::factory()->create([
            'driver_id' => $driver->id,
            'date' => now()->toDateString(),
            'time_in' => now()->subHours(2)->format('h:i A'),
            'time_out' => null,
        ]);

        $this->actingAs($user)->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('driver.timekeeping.clock-out'))
            ->assertSessionHas('success');

        $this->assertSame('inactive', $driver->fresh()->status);
        $this->assertNotNull($driver->timeKeeping()->whereDate('date', today())->first()->time_out);
    }

    /** E009 — a second clock-in on the same day is refused. */
    public function test_driver_cannot_clock_in_twice_in_one_day()
    {
        [$user, $driver] = $this->driver('inactive');

        $this->actingAs($user)->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('driver.timekeeping.clock-in'))
            ->assertSessionHas('success');

        $this->actingAs($user)->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('driver.timekeeping.clock-in'))
            ->assertSessionHas('error');

        $this->assertSame(1, TimeKeeping::where('driver_id', $driver->id)->count());
    }

    /** E009 — clock-in without a driver record must not crash. */
    public function test_clock_in_without_a_driver_profile_shows_an_error()
    {
        $user = User::factory()->create()->assignRole('driver');
        $user->markEmailAsVerified();

        $before = TimeKeeping::count();

        $response = $this->actingAs($user)->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('driver.timekeeping.clock-in'));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame($before, TimeKeeping::count());
    }

    /** E008 — changing the password keeps the user signed in on this device. */
    public function test_changing_password_keeps_current_session()
    {
        $user = $this->commuter();

        $this->actingAs($user)->withoutMiddleware(VerifyCsrfToken::class)
            ->put(route('settings.password'), [
                'current_password' => '123456789',
                'password' => 'brand-new-secret',
                'password_confirmation' => 'brand-new-secret',
            ])
            ->assertSessionHas('success');

        $this->assertAuthenticated();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('brand-new-secret', $user->fresh()->password));
    }

    /** E008 — E1 wrong current password changes nothing. */
    public function test_changing_password_requires_the_correct_current_password()
    {
        $user = $this->commuter();
        $original = $user->password;

        $this->actingAs($user)->withoutMiddleware(VerifyCsrfToken::class)
            ->put(route('settings.password'), [
                'current_password' => 'not-the-password',
                'password' => 'brand-new-secret',
                'password_confirmation' => 'brand-new-secret',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertSame($original, $user->fresh()->password);
    }

    /** E008 — E3 confirmation mismatch. */
    public function test_changing_password_requires_matching_confirmation()
    {
        $user = $this->commuter();
        $original = $user->password;

        $this->actingAs($user)->withoutMiddleware(VerifyCsrfToken::class)
            ->put(route('settings.password'), [
                'current_password' => '123456789',
                'password' => 'brand-new-secret',
                'password_confirmation' => 'something-else',
            ])
            ->assertSessionHasErrors('password');

        $this->assertSame($original, $user->fresh()->password);
    }
}