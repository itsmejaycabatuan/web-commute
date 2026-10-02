<?php

namespace Tests\Feature;

use App\Models\Fare;
use App\Models\FareRate;
use App\Models\Payment;
use App\Models\User;
use App\Models\Wallet;
use App\Services\FareCalculator;
use App\Services\PayMongoService;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * PayMongo gateway integration.
 *
 * Every gateway call is faked, so the whole suite runs with **no credentials**.
 * The sandbox smoke test is the only step that needs real test keys.
 */
class PayMongoPaymentTest extends TestCase
{
    use DatabaseTransactions;

    protected const SECRET = 'sk_test_fake_key';
    protected const API = 'https://api.paymongo.com/v1/*';

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'commuter']);

        // A small deterministic rate table: 5 km => ₱17.25 (=> 18), 10 km => ₱28.25 (=> 29)
        $fare = Fare::factory()->create();
        FareRate::factory()->create(['fare_id' => $fare->id, 'km' => 1, 'regular' => 15, 'discount' => 12]);
        FareRate::factory()->create(['fare_id' => $fare->id, 'km' => 5, 'regular' => 17.25, 'discount' => 13.75]);
        FareRate::factory()->create(['fare_id' => $fare->id, 'km' => 10, 'regular' => 28.25, 'discount' => 22.5]);

        $this->enableGateway(false);
        PayMongoService::flushConfigCache();
    }

    protected function enableGateway(bool $enabled = true): void
    {
        config([
            'paymongo.enabled' => $enabled,
            'paymongo.secret_key' => self::SECRET,
            'paymongo.public_key' => 'pk_test_fake',
            'paymongo.base_url' => 'https://api.paymongo.com/v1',
            'paymongo.currency' => 'PHP',
            'paymongo.minor_unit_factor' => 100,
        ]);

        PayMongoService::flushConfigCache();
    }

    /** Switch keys/host without touching anything else. */
    protected function gatewayConfig(string $secret, string $baseUrl, string $env = 'testing'): void
    {
        config([
            'paymongo.enabled' => true,
            'paymongo.secret_key' => $secret,
            'paymongo.base_url' => $baseUrl,
        ]);
        app()['env'] = $env;
        PayMongoService::flushConfigCache();
    }

    protected function commuter(float $balance = 500): User
    {
        $user = User::factory()->create()->assignRole('commuter');
        $user->markEmailAsVerified();
        Wallet::factory()->create(['user_id' => $user->id, 'balance' => (string) $balance]);

        return $user->fresh();
    }

    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'pickup' => 'Minglanilla',
            'destination' => 'IT Park',
            'distance' => '5.2',
            'amount' => 18,
            'payment-method' => 'GCash',
            'transaction-id' => '#SC-PM0001',
        ], $overrides);
    }

    /** Fake a successful checkout session creation (PayMongo creates the intent). */
    protected function fakeHappyPathGateway(string $intentId = 'pi_test_123', string $sessionId = 'cs_test_123'): void
    {
        Http::fake([
            'https://api.paymongo.com/v1/checkout_sessions' => Http::response([
                'data' => [
                    'id' => $sessionId,
                    'type' => 'checkout_session',
                    'attributes' => [
                        'checkout_url' => 'https://checkout.paymongo.com/'.$sessionId,
                        'status' => 'active',
                        'payment_method_types' => ['gcash'],
                        'payment_intent' => [
                            'id' => $intentId,
                            'type' => 'payment_intent',
                            'attributes' => ['status' => 'awaiting_payment_method', 'amount' => 1800],
                        ],
                    ],
                ],
            ]),
        ]);
    }

    protected function signWebhook(string $body): array
    {
        return [
            'HTTP_PAYMONGO_SIGNATURE' => hash_hmac('sha256', $body, self::SECRET),
            'HTTP_PAYMONGO_TIMESTAMP' => '1700000000',
            'HTTP_PAYMONGO_TE_SIGNATURE' => hash_hmac('sha256', '1700000000.'.$body, self::SECRET),
        ];
    }

    protected function webhookPayload(string $type, string $intentId): string
    {
        return json_encode([
            'data' => [
                'id' => 'evt_1',
                'attributes' => [
                    'type' => $type,
                    'data' => [
                        'id' => $intentId,
                        'attributes' => [
                            'metadata' => ['transaction_id' => '#SC-PM0001'],
                        ],
                    ],
                ],
            ],
        ]);
    }

    // ── Feature flag ─────────────────────────────────────────────────────

    public function test_gateway_is_disabled_by_default_and_records_gcash_as_declared()
    {
        Http::fake();

        $user = $this->commuter();

        $this->actingAs($user)->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('payment.process'), $this->payload());

        Http::assertNothingSent();

        $payment = Payment::where('transaction_id', '#SC-PM0001')->firstOrFail();
        $this->assertSame('paid', $payment->status);
        $this->assertSame('GCash', $payment->payment_method);
    }

    // ── Gateway happy path ───────────────────────────────────────────────

    public function test_gcash_payment_creates_a_checkout_session_and_redirects_away()
    {
        $this->enableGateway();
        $this->fakeHappyPathGateway();

        $user = $this->commuter();

        $response = $this->actingAs($user)->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('payment.process'), $this->payload());

        $response->assertRedirect('https://checkout.paymongo.com/cs_test_123');

        $payment = Payment::where('transaction_id', '#SC-PM0001')->firstOrFail();

        $this->assertSame('pending', $payment->status);
        $this->assertSame('pi_test_123', $payment->paymongo_payment_intent_id);
        $this->assertSame('cs_test_123', $payment->paymongo_checkout_session_id);
        // PayMongo exposes no reference_id on a payment intent.
        $this->assertNull($payment->paymongo_reference_id);
        $this->assertNull($payment->paid_at);

        // The wallet is untouched until settlement.
        $this->assertEquals(500.0, (float) Wallet::where('user_id', $user->id)->value('balance'));
    }

    public function test_gateway_request_is_sent_in_centavos_with_the_server_price()
    {
        $this->enableGateway();
        $this->fakeHappyPathGateway();

        $this->actingAs($this->commuter())->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('payment.process'), $this->payload(['distance' => '10.0']));

        // Server prices 10 km at ceil(28.25) = ₱29 => 2900 centavos in line_items.
        Http::assertSent(function (Request $request) {
            $body = json_decode($request->body(), true);
            $lineItems = $body['data']['attributes']['line_items'] ?? [];

            return str_contains($request->url(), '/checkout_sessions')
                && ($lineItems[0]['amount'] ?? null) === 2900;
        });
    }

    /** PayMongo rejects a session without an explicit channel. */
    public function test_checkout_session_declares_the_allowed_payment_channel()
    {
        $this->enableGateway();
        $this->fakeHappyPathGateway();

        $this->actingAs($this->commuter())->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('payment.process'), $this->payload());

        Http::assertSent(function (Request $request) {
            $body = json_decode($request->body(), true);

            return str_contains($request->url(), '/checkout_sessions')
                && ($body['data']['attributes']['payment_method_types'] ?? null) === ['gcash'];
        });

        $this->actingAs($this->commuter())->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('payment.process'), $this->payload([
                'payment-method' => 'Maya',
                'transaction-id' => '#SC-PM0002',
            ]));

        Http::assertSent(function (Request $request) {
            $body = json_decode($request->body(), true);

            return str_contains($request->url(), '/checkout_sessions')
                && ($body['data']['attributes']['payment_method_types'] ?? null) === ['maya'];
        });
    }

    // ── Configuration safety rail ────────────────────────────────────────

    public function test_live_key_against_the_sandbox_host_refuses_to_run()
    {
        $this->gatewayConfig('sk_live_abcdefgh', 'https://api.paymongo.com/v1');

        $problems = app(PayMongoService::class)->configProblems();

        $this->assertNotEmpty($problems);
        $this->assertStringContainsString('LIVE secret key', implode(' ', $problems));
        $this->assertFalse(app(PayMongoService::class)->enabled());
    }

    public function test_sandbox_key_against_the_live_host_refuses_to_run()
    {
        $this->gatewayConfig('sk_test_abcdefgh', 'https://api.live.paymongo.com/v1');

        $this->assertFalse(app(PayMongoService::class)->enabled());
        $this->assertStringContainsString('LIVE host', implode(' ', app(PayMongoService::class)->configProblems()));
    }

    public function test_live_key_outside_production_refuses_to_run()
    {
        $this->gatewayConfig('sk_live_abcdefgh', 'https://api.live.paymongo.com/v1', 'local');

        $this->assertFalse(app(PayMongoService::class)->enabled());
        $this->assertStringContainsString('local', implode(' ', app(PayMongoService::class)->configProblems()));
    }

    public function test_unknown_host_refuses_to_run()
    {
        $this->gatewayConfig('sk_test_abcdefgh', 'https://api.paymongo.example.com/v1');

        $this->assertFalse(app(PayMongoService::class)->enabled());
    }

    public function test_missing_secret_key_refuses_to_run()
    {
        $this->gatewayConfig('', 'https://api.paymongo.com/v1');

        $this->assertFalse(app(PayMongoService::class)->enabled());
    }

    /** A refused gateway must degrade safely, not 500. */
    public function test_refused_gateway_falls_back_to_a_declared_payment()
    {
        $this->gatewayConfig('sk_live_abcdefgh', 'https://api.paymongo.com/v1');
        Http::fake();

        $this->actingAs($this->commuter())->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('payment.process'), $this->payload());

        Http::assertNothingSent();
        $this->assertSame('paid', Payment::where('transaction_id', '#SC-PM0001')->value('status'));
    }

    public function test_a_valid_sandbox_setup_passes_the_audit()
    {
        $this->enableGateway();

        $this->assertSame([], app(PayMongoService::class)->configProblems());
        $this->assertTrue(app(PayMongoService::class)->enabled());
    }

    /** The submitted amount is never trusted. */
    public function test_tampered_amount_is_ignored_and_the_fare_is_repriced_server_side()
    {
        $this->enableGateway();
        $this->fakeHappyPathGateway();

        $user = $this->commuter();

        $this->actingAs($user)->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('payment.process'), $this->payload(['amount' => 1]));

        $payment = Payment::where('transaction_id', '#SC-PM0001')->firstOrFail();

        // 5.2 km => tier 5 => ceil(17.25) = 18, not the posted ₱1.
        $this->assertEquals(18.0, (float) $payment->price);
    }

    public function test_wallet_payment_stays_internal_even_when_the_gateway_is_enabled()
    {
        $this->enableGateway();
        Http::fake();

        $user = $this->commuter();

        $response = $this->actingAs($user)->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('payment.process'), $this->payload(['payment-method' => 'Wallet']));

        Http::assertNothingSent();

        $payment = Payment::where('transaction_id', '#SC-PM0001')->firstOrFail();
        $response->assertRedirect(route('payment.showReceipt', $payment->id));
        $this->assertSame('paid', $payment->status);
        $this->assertEquals(482.0, (float) Wallet::where('user_id', $user->id)->value('balance'));
    }

    public function test_gateway_failure_does_not_create_a_payment()
    {
        $this->enableGateway();
        Http::fake([
            self::API => Http::response(['errors' => [['detail' => 'Bad request']]], 422),
        ]);

        $this->actingAs($this->commuter())->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('payment.process'), $this->payload())
            ->assertSessionHas('error');

        $this->assertSame(0, Payment::where('transaction_id', '#SC-PM0001')->count());
    }

    // ── Webhook ──────────────────────────────────────────────────────────

    public function test_webhook_rejects_an_unsigned_request()
    {
        $this->enableGateway();

        $this->postJson(route('paymongo.webhook'), [])->assertStatus(401);
    }

    public function test_webhook_rejects_a_tampered_signature()
    {
        $this->enableGateway();
        $body = $this->webhookPayload('payment.paid', 'pi_test_123');

        $this->call('POST', route('paymongo.webhook'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_PAYMONGO_SIGNATURE' => 'deadbeef',
        ], $body)->assertStatus(401);
    }

    public function test_webhook_settles_a_pending_payment()
    {
        $this->enableGateway();
        $this->fakeHappyPathGateway();

        $user = $this->commuter();
        $this->actingAs($user)->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('payment.process'), $this->payload());

        $body = $this->webhookPayload('payment.paid', 'pi_test_123');

        $this->call('POST', route('paymongo.webhook'), [], [], [], array_merge(
            ['CONTENT_TYPE' => 'application/json'],
            $this->signWebhook($body)
        ), $body)->assertOk();

        $payment = Payment::where('transaction_id', '#SC-PM0001')->firstOrFail();
        $this->assertSame('paid', $payment->status);
        $this->assertNotNull($payment->paid_at);
    }

    public function test_replayed_webhook_is_idempotent()
    {
        $this->enableGateway();
        $this->fakeHappyPathGateway();

        $this->actingAs($this->commuter())->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('payment.process'), $this->payload());

        $body = $this->webhookPayload('payment.paid', 'pi_test_123');
        $headers = array_merge(['CONTENT_TYPE' => 'application/json'], $this->signWebhook($body));

        $this->call('POST', route('paymongo.webhook'), [], [], [], $headers, $body)->assertOk();
        $firstPaidAt = Payment::where('transaction_id', '#SC-PM0001')->value('paid_at');

        $this->call('POST', route('paymongo.webhook'), [], [], [], $headers, $body)->assertOk();

        $payment = Payment::where('transaction_id', '#SC-PM0001')->firstOrFail();
        $this->assertSame('paid', $payment->status);
        $this->assertEquals($firstPaidAt, $payment->paid_at);
    }

    public function test_webhook_marks_a_declined_payment_failed()
    {
        $this->enableGateway();
        $this->fakeHappyPathGateway();

        $this->actingAs($this->commuter())->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('payment.process'), $this->payload());

        $body = $this->webhookPayload('payment.failed', 'pi_test_123');

        $this->call('POST', route('paymongo.webhook'), [], [], [], array_merge(
            ['CONTENT_TYPE' => 'application/json'],
            $this->signWebhook($body)
        ), $body)->assertOk();

        $payment = Payment::where('transaction_id', '#SC-PM0001')->firstOrFail();
        $this->assertSame('failed', $payment->status);
        $this->assertNotNull($payment->failed_at);
    }

    public function test_webhook_for_an_unknown_payment_is_acknowledged()
    {
        $this->enableGateway();

        $body = $this->webhookPayload('payment.paid', 'pi_does_not_exist');

        $this->call('POST', route('paymongo.webhook'), [], [], [], array_merge(
            ['CONTENT_TYPE' => 'application/json'],
            $this->signWebhook($body)
        ), $body)->assertOk();
    }

    // ── Return / cancel reconciliation ───────────────────────────────────

    public function test_return_url_reconciles_a_payment_whose_webhook_was_missed()
    {
        $this->enableGateway();
        $this->fakeHappyPathGateway();

        $user = $this->commuter();
        $this->actingAs($user)->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('payment.process'), $this->payload());

        // The webhook never arrives, but the intent is settled at PayMongo.
        Http::fake([
            'https://api.paymongo.com/v1/payment_intents/pi_test_123' => Http::response([
                'data' => [
                    'id' => 'pi_test_123',
                    'type' => 'payment_intent',
                    'attributes' => ['status' => 'paid'],
                ],
            ]),
        ]);

        $payment = Payment::where('transaction_id', '#SC-PM0001')->firstOrFail();

        $this->actingAs($user)
            ->get(route('payment.returned', ['ref' => '#SC-PM0001']))
            ->assertRedirect(route('payment.showReceipt', $payment->id));

        $this->assertSame('paid', $payment->fresh()->status);
    }

    /**
     * Regression: PayMongo reports a successful payment as **succeeded**, not
     * "paid". Missing that left real successful payments stuck on the pending
     * screen forever.
     */
    public function test_succeeded_intent_status_is_treated_as_settled()
    {
        $this->enableGateway();
        $this->fakeHappyPathGateway();

        $user = $this->commuter();
        $this->actingAs($user)->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('payment.process'), $this->payload());

        Http::fake([
            'https://api.paymongo.com/v1/payment_intents/pi_test_123' => Http::response([
                'data' => [
                    'id' => 'pi_test_123',
                    'type' => 'payment_intent',
                    'attributes' => ['status' => 'succeeded'],
                ],
            ]),
        ]);

        $payment = Payment::where('transaction_id', '#SC-PM0001')->firstOrFail();

        $this->actingAs($user)
            ->get(route('payment.returned', ['ref' => '#SC-PM0001']))
            ->assertRedirect(route('payment.showReceipt', $payment->id));

        $this->assertSame('paid', $payment->fresh()->status);
    }

    public function test_is_settled_accepts_the_real_paymongo_statuses()
    {
        $svc = app(PayMongoService::class);

        $this->assertTrue($svc->isSettled('succeeded'));
        $this->assertTrue($svc->isSettled('paid'));
        $this->assertTrue($svc->isSettled('SUCCEEDED'));
        $this->assertFalse($svc->isSettled('awaiting_payment'));
        $this->assertFalse($svc->isSettled('awaiting_payment_method'));
        $this->assertFalse($svc->isSettled(null));

        $this->assertTrue($svc->isFailed('failed'));
        $this->assertTrue($svc->isFailed('canceled'));
        $this->assertFalse($svc->isFailed('succeeded'));
    }

    /** A gateway that gives up must stop the spinner and say so. */
    public function test_return_url_stops_the_spinner_when_the_gateway_fails_the_payment()
    {
        $this->enableGateway();
        $this->fakeHappyPathGateway();

        $user = $this->commuter();
        $this->actingAs($user)->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('payment.process'), $this->payload());

        Http::fake([
            'https://api.paymongo.com/v1/payment_intents/pi_test_123' => Http::response([
                'data' => [
                    'id' => 'pi_test_123',
                    'type' => 'payment_intent',
                    'attributes' => ['status' => 'failed'],
                ],
            ]),
        ]);

        $this->actingAs($user)
            ->get(route('payment.returned', ['ref' => '#SC-PM0001']))
            ->assertRedirect(route('payment.history'))
            ->assertSessionHas('error');

        $this->assertSame('failed', Payment::where('transaction_id', '#SC-PM0001')->value('status'));
    }

    /** The pending screen must auto-refresh even with JS blocked. */
    public function test_pending_screen_has_a_meta_refresh_fallback_and_icons()
    {
        $this->enableGateway();
        $this->fakeHappyPathGateway();

        $user = $this->commuter();
        $this->actingAs($user)->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('payment.process'), $this->payload());

        Http::fake([
            'https://api.paymongo.com/v1/payment_intents/pi_test_123' => Http::response([
                'data' => [
                    'id' => 'pi_test_123',
                    'type' => 'payment_intent',
                    'attributes' => ['status' => 'awaiting_payment'],
                ],
            ]),
        ]);

        $response = $this->actingAs($user)
            ->get(route('payment.returned', ['ref' => '#SC-PM0001']))
            ->assertOk();

        // JS-free refresh, JS refresh, and the icon stylesheet.
        $response->assertSee('http-equiv="refresh"', false);
        $response->assertSee('font-awesome', false);
        $response->assertSee('Check again');
    }

    public function test_return_url_shows_processing_when_the_gateway_is_still_pending()
    {
        $this->enableGateway();
        $this->fakeHappyPathGateway();

        $user = $this->commuter();
        $this->actingAs($user)->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('payment.process'), $this->payload());

        Http::fake([
            'https://api.paymongo.com/v1/payment_intents/pi_test_123' => Http::response([
                'data' => [
                    'id' => 'pi_test_123',
                    'type' => 'payment_intent',
                    'attributes' => ['status' => 'awaiting_payment'],
                ],
            ]),
        ]);

        $this->actingAs($user)
            ->get(route('payment.returned', ['ref' => '#SC-PM0001']))
            ->assertOk()
            ->assertSee('Confirming your payment');

        $this->assertSame('pending', Payment::where('transaction_id', '#SC-PM0001')->value('status'));
    }

    public function test_cancel_url_marks_the_payment_cancelled_and_charges_nothing()
    {
        $this->enableGateway();
        $this->fakeHappyPathGateway();

        $user = $this->commuter();
        $this->actingAs($user)->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('payment.process'), $this->payload());

        $this->actingAs($user)
            ->get(route('payment.cancelled', ['ref' => '#SC-PM0001']))
            ->assertRedirect(route('payment.history'));

        $this->assertSame('cancelled', Payment::where('transaction_id', '#SC-PM0001')->value('status'));
        $this->assertEquals(500.0, (float) Wallet::where('user_id', $user->id)->value('balance'));
    }

    public function test_return_url_ignores_another_commuters_reference()
    {
        $this->enableGateway();
        $this->fakeHappyPathGateway();

        $owner = $this->commuter();
        $this->actingAs($owner)->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('payment.process'), $this->payload());

        $stranger = $this->commuter();

        $this->actingAs($stranger)
            ->get(route('payment.returned', ['ref' => '#SC-PM0001']))
            ->assertRedirect(route('payment.history'));
    }

    // ── Fare pricing ─────────────────────────────────────────────────────

    public function test_fare_calculator_picks_the_correct_tier()
    {
        $calculator = app(FareCalculator::class);

        $this->assertEquals(18.0, $calculator->regularFare(5.2));   // tier 5  => ceil(17.25)
        $this->assertEquals(29.0, $calculator->regularFare(10.0));  // tier 10 => ceil(28.25)
        $this->assertEquals(15.0, $calculator->regularFare(0.4));   // below first tier
        $this->assertEquals(29.0, $calculator->regularFare(99.0));  // above top tier
    }
}