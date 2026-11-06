<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\TopupHistory;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\TopupFailed;
use App\Notifications\TopupReceipt;
use App\Services\PayMongoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * UCN_SC_E005 — wallet top-ups settle through PayMongo (test mode). Every gateway call is faked, so the suite
 * needs no credentials; the wallet is credited only when the gateway confirms the money.
 */
class TopupGatewayTest extends TestCase
{
    use DatabaseTransactions;

    protected const SECRET = 'sk_test_fake_key';

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'commuter']);
        Role::firstOrCreate(['name' => 'admin']);
        $this->gateway(true);
    }

    protected function gateway(bool $enabled, string $secret = self::SECRET, string $env = 'testing'): void
    {
        config([
            'paymongo.enabled' => $enabled,
            'paymongo.secret_key' => $secret,
            'paymongo.public_key' => 'pk_test_fake',
            'paymongo.base_url' => 'https://api.paymongo.com/v1',
            'paymongo.currency' => 'PHP',
            'paymongo.minor_unit_factor' => 100,
        ]);
        app()['env'] = $env;
        PayMongoService::flushConfigCache();
    }

    protected function commuter(float $balance = 100): User
    {
        $user = User::factory()->create()->assignRole('commuter');
        $user->markEmailAsVerified();
        Wallet::factory()->create(['user_id' => $user->id, 'balance' => (string) $balance]);

        return $user->fresh();
    }

    protected function balance(User $user): float
    {
        return (float) Wallet::where('user_id', $user->id)->value('balance');
    }

    protected function fakeCheckout(string $intentId = 'pi_topup_1', string $sessionId = 'cs_topup_1'): void
    {
        Http::swap(new Factory());   // a fresh fake: stubs registered earlier would win over this one
        Http::fake([
            'https://api.paymongo.com/v1/checkout_sessions' => Http::response(['data' => [
                'id' => $sessionId,
                'attributes' => [
                    'checkout_url' => 'https://checkout.paymongo.com/'.$sessionId,
                    'status' => 'active',
                    'payment_intent' => ['id' => $intentId, 'attributes' => ['status' => 'awaiting_payment_method']],
                ],
            ]]),
        ]);
    }

    protected function fakeIntent(string $intentId, string $status): void
    {
        Http::swap(new Factory());
        Http::fake([
            'https://api.paymongo.com/v1/payment_intents/'.$intentId => Http::response(['data' => [
                'id' => $intentId,
                'attributes' => ['status' => $status, 'amount' => 10000],
            ]]),
        ]);
    }

    /** Start a top-up through the gateway and return its pending row. */
    protected function startTopup(User $user, float $amount = 100, string $method = 'gcash'): TopupHistory
    {
        $this->fakeCheckout();
        $this->actingAs($user)->post(route('payment.topup.process'), ['amount' => $amount, 'payment-method' => $method])
            ->assertRedirect('https://checkout.paymongo.com/cs_topup_1');

        return TopupHistory::where('user_id', $user->id)->latest('id')->firstOrFail();
    }

    protected function signedWebhook(string $type, string $intentId, ?string $transactionId = null)
    {
        $body = json_encode(['data' => ['id' => 'evt_1', 'attributes' => ['type' => $type, 'data' => [
            'id' => $intentId,
            'attributes' => ['metadata' => array_filter(['transaction_id' => $transactionId])],
        ]]]]);

        return $this->call('POST', route('paymongo.webhook'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_PAYMONGO_SIGNATURE' => hash_hmac('sha256', $body, self::SECRET),
        ], $body);
    }

    // ── Starting a top-up ────────────────────────────────────────────────

    /**
     * @group NFR-REL-18
     * @group NFR-REL
     */
    public function test_a_gcash_top_up_opens_a_checkout_and_leaves_the_wallet_alone()
    {
        $user = $this->commuter(100);
        $topup = $this->startTopup($user, 250);

        $this->assertSame(TopupHistory::STATUS_PENDING, $topup->status);
        $this->assertEquals(250.0, (float) $topup->amount_added);
        $this->assertSame('pi_topup_1', $topup->paymongo_payment_intent_id);
        $this->assertSame('cs_topup_1', $topup->paymongo_checkout_session_id);
        $this->assertStringStartsWith('TOPUP-', $topup->transaction_id);
        $this->assertNull($topup->paid_at);
        $this->assertEquals(100.0, $this->balance($user));
    }

    public function test_the_checkout_is_sent_in_centavos_for_the_chosen_channel_with_our_return_urls()
    {
        $user = $this->commuter();
        $topup = $this->startTopup($user, 75.50, 'maya');

        Http::assertSent(function ($request) use ($topup) {
            $a = $request->data()['data']['attributes'];

            return str_ends_with($request->url(), '/checkout_sessions')
                && $a['line_items'][0]['amount'] === 7550
                && $a['payment_method_types'] === ['paymaya']
                && $a['success_url'] === route('payment.topup.returned', ['ref' => $topup->transaction_id])
                && $a['cancel_url'] === route('payment.topup.cancelled', ['ref' => $topup->transaction_id])
                && $a['metadata']['transaction_id'] === $topup->transaction_id
                && $a['metadata']['type'] === 'topup';
        });
    }

    public function test_a_gateway_error_creates_no_top_up_and_no_credit()
    {
        Http::fake(['https://api.paymongo.com/v1/checkout_sessions' => Http::response(['errors' => [['detail' => 'nope']]], 400)]);
        $user = $this->commuter(100);

        $this->actingAs($user)->post(route('payment.topup.process'), ['amount' => 100, 'payment-method' => 'gcash'])
            ->assertSessionHas('error');

        $this->assertSame(0, TopupHistory::where('user_id', $user->id)->count());
        $this->assertEquals(100.0, $this->balance($user));
    }

    public function test_the_amount_and_method_are_still_validated()
    {
        $user = $this->commuter();
        Http::fake();

        $this->actingAs($user)->post(route('payment.topup.process'), ['amount' => 5, 'payment-method' => 'gcash'])->assertSessionHasErrors('amount');
        $this->actingAs($user)->post(route('payment.topup.process'), ['amount' => 100, 'payment-method' => 'admin'])->assertSessionHasErrors('payment-method');

        Http::assertNothingSent();
    }

    public function test_a_pending_top_up_does_not_block_trying_again()
    {
        $user = $this->commuter();
        $this->startTopup($user, 100);

        $this->fakeCheckout('pi_topup_2', 'cs_topup_2');
        $this->actingAs($user)->post(route('payment.topup.process'), ['amount' => 100, 'payment-method' => 'gcash'])
            ->assertRedirect('https://checkout.paymongo.com/cs_topup_2');

        $this->assertSame(2, TopupHistory::where('user_id', $user->id)->count());
    }

    // ── No gateway ───────────────────────────────────────────────────────

    public function test_with_the_gateway_off_a_dev_environment_simulates_the_top_up_at_once()
    {
        $this->gateway(false);
        Notification::fake();
        $user = $this->commuter(0);

        $this->actingAs($user)->post(route('payment.topup.process'), ['amount' => 50, 'payment-method' => 'gcash'])
            ->assertSessionHas('success');

        $this->assertEquals(50.0, $this->balance($user));
        $topup = TopupHistory::where('user_id', $user->id)->firstOrFail();
        $this->assertSame(TopupHistory::STATUS_COMPLETED, $topup->status);
        Notification::assertSentTo($user, TopupReceipt::class);
    }

    /**
     * @group NFR-REL-18
     * @group NFR-REL
     */
    public function test_production_without_a_gateway_never_credits_a_wallet()
    {
        $this->gateway(false, self::SECRET, 'production');
        $user = $this->commuter(0);

        // (Changing the environment turns CSRF protection back on, which is not what is under test.)
        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->actingAs($user)->post(route('payment.topup.process'), ['amount' => 50, 'payment-method' => 'gcash'])
            ->assertSessionHas('error');

        $this->assertEquals(0.0, $this->balance($user));
        $this->assertSame(0, TopupHistory::where('user_id', $user->id)->count());
    }

    /**
     * @group NFR-REL-18
     * @group NFR-REL
     */
    public function test_a_gateway_that_refuses_to_run_never_falls_back_to_free_money()
    {
        // enabled, but a LIVE key against the sandbox host: the safety audit refuses it.
        $this->gateway(true, 'sk_live_fake');
        $user = $this->commuter(0);

        $this->actingAs($user)->post(route('payment.topup.process'), ['amount' => 50, 'payment-method' => 'gcash'])
            ->assertSessionHas('error');

        $this->assertEquals(0.0, $this->balance($user));
        $this->assertSame(0, TopupHistory::where('user_id', $user->id)->count());
    }

    // ── Webhook ──────────────────────────────────────────────────────────

    /**
     * @group NFR-REL-18
     * @group NFR-REL
     */
    public function test_the_webhook_credits_the_wallet_and_emails_a_receipt()
    {
        $user = $this->commuter(100);
        $topup = $this->startTopup($user, 250);
        Notification::fake();

        $this->signedWebhook('payment.paid', 'pi_topup_1')->assertOk();

        $topup->refresh();
        $this->assertSame(TopupHistory::STATUS_COMPLETED, $topup->status);
        $this->assertNotNull($topup->paid_at);
        $this->assertEquals(350.0, $this->balance($user));
        Notification::assertSentTo($user, TopupReceipt::class, fn ($n) => abs($n->newBalance - 350) < 0.001);
    }

    /**
     * @group NFR-REL-18
     * @group NFR-REL
     */
    public function test_a_replayed_webhook_credits_only_once()
    {
        $user = $this->commuter(100);
        $this->startTopup($user, 250);
        Notification::fake();

        $this->signedWebhook('payment.paid', 'pi_topup_1')->assertOk();
        $this->signedWebhook('payment.paid', 'pi_topup_1')->assertOk();

        $this->assertEquals(350.0, $this->balance($user));
        Notification::assertSentToTimes($user, TopupReceipt::class, 1);
    }

    public function test_the_webhook_finds_the_top_up_by_the_payment_intent_in_a_payment_event()
    {
        // A real payment.paid event: the resource is the payment (pay_…), the intent is one of its attributes.
        $user = $this->commuter(0);
        $this->startTopup($user, 40);

        $body = json_encode(['data' => ['attributes' => ['type' => 'payment.paid', 'data' => [
            'id' => 'pay_123', 'type' => 'payment',
            'attributes' => ['payment_intent_id' => 'pi_topup_1', 'amount' => 4000],
        ]]]]);
        $this->call('POST', route('paymongo.webhook'), [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_PAYMONGO_SIGNATURE' => hash_hmac('sha256', $body, self::SECRET),
        ], $body)->assertOk();

        $this->assertEquals(40.0, $this->balance($user));
    }

    public function test_the_webhook_falls_back_to_the_transaction_id_in_the_metadata()
    {
        $user = $this->commuter(0);
        $topup = $this->startTopup($user, 40);

        $this->signedWebhook('payment.paid', 'pi_something_else', $topup->transaction_id)->assertOk();

        $this->assertEquals(40.0, $this->balance($user));
    }

    /**
     * @group NFR-REL-18
     * @group NFR-REL
     */
    public function test_an_unsigned_or_forged_webhook_credits_nothing()
    {
        $user = $this->commuter(0);
        $this->startTopup($user, 40);

        $body = json_encode(['data' => ['attributes' => ['type' => 'payment.paid', 'data' => ['id' => 'pi_topup_1']]]]);
        $this->call('POST', route('paymongo.webhook'), [], [], [], ['CONTENT_TYPE' => 'application/json'], $body)->assertStatus(401);
        $this->call('POST', route('paymongo.webhook'), [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_PAYMONGO_SIGNATURE' => hash_hmac('sha256', $body, 'sk_test_wrong'),
        ], $body)->assertStatus(401);

        $this->assertEquals(0.0, $this->balance($user));
    }

    public function test_a_failed_payment_event_closes_the_top_up_and_tells_the_commuter()
    {
        $user = $this->commuter(100);
        $topup = $this->startTopup($user, 250);
        Notification::fake();

        $this->signedWebhook('payment.failed', 'pi_topup_1')->assertOk();

        $this->assertSame(TopupHistory::STATUS_FAILED, $topup->refresh()->status);
        $this->assertEquals(100.0, $this->balance($user));
        Notification::assertSentTo($user, TopupFailed::class);
    }

    /**
     * @group NFR-REL-18
     * @group NFR-REL
     */
    public function test_money_that_arrives_after_the_commuter_backed_out_is_still_credited()
    {
        $user = $this->commuter(100);
        $topup = $this->startTopup($user, 250);
        $this->actingAs($user)->get(route('payment.topup.cancelled', ['ref' => $topup->transaction_id]));
        $this->assertSame(TopupHistory::STATUS_CANCELLED, $topup->refresh()->status);

        $this->signedWebhook('payment.paid', 'pi_topup_1')->assertOk();

        $this->assertSame(TopupHistory::STATUS_COMPLETED, $topup->refresh()->status);
        $this->assertEquals(350.0, $this->balance($user));
    }

    // ── Return URL ───────────────────────────────────────────────────────

    /**
     * @group NFR-REL-18
     * @group NFR-REL
     */
    public function test_the_return_url_credits_a_top_up_whose_webhook_was_missed()
    {
        $user = $this->commuter(100);
        $topup = $this->startTopup($user, 250);
        $this->fakeIntent('pi_topup_1', 'succeeded');

        $this->actingAs($user)->get(route('payment.topup.returned', ['ref' => $topup->transaction_id]))
            ->assertRedirect(route('payment.topup'))
            ->assertSessionHas('success');

        $this->assertSame(TopupHistory::STATUS_COMPLETED, $topup->refresh()->status);
        $this->assertEquals(350.0, $this->balance($user));
    }

    public function test_the_return_url_shows_processing_while_the_gateway_is_still_waiting()
    {
        $user = $this->commuter(100);
        $topup = $this->startTopup($user, 250);
        $this->fakeIntent('pi_topup_1', 'awaiting_payment_method');

        $this->actingAs($user)->get(route('payment.topup.returned', ['ref' => $topup->transaction_id]))
            ->assertOk()
            ->assertSee('Confirming your top-up')
            ->assertSee($topup->transaction_id);

        $this->assertSame(TopupHistory::STATUS_PENDING, $topup->refresh()->status);
        $this->assertEquals(100.0, $this->balance($user));
    }

    public function test_the_return_url_stops_the_spinner_when_the_gateway_fails_the_payment()
    {
        $user = $this->commuter(100);
        $topup = $this->startTopup($user, 250);
        $this->fakeIntent('pi_topup_1', 'failed');

        $this->actingAs($user)->get(route('payment.topup.returned', ['ref' => $topup->transaction_id]))
            ->assertRedirect(route('payment.topup'))
            ->assertSessionHas('error');

        $this->assertSame(TopupHistory::STATUS_FAILED, $topup->refresh()->status);
        $this->assertEquals(100.0, $this->balance($user));
    }

    public function test_the_return_url_ignores_another_commuters_top_up()
    {
        $owner = $this->commuter(100);
        $topup = $this->startTopup($owner, 250);
        $this->fakeIntent('pi_topup_1', 'succeeded');

        $this->actingAs($this->commuter(0))->get(route('payment.topup.returned', ['ref' => $topup->transaction_id]))
            ->assertRedirect(route('payment.topup.history'))
            ->assertSessionHas('error');

        $this->assertSame(TopupHistory::STATUS_PENDING, $topup->refresh()->status);
        $this->assertEquals(100.0, $this->balance($owner));
    }

    public function test_cancelling_at_checkout_closes_the_top_up_and_charges_nothing()
    {
        $user = $this->commuter(100);
        $topup = $this->startTopup($user, 250);

        $this->actingAs($user)->get(route('payment.topup.cancelled', ['ref' => $topup->transaction_id]))
            ->assertRedirect(route('payment.topup'))
            ->assertSessionHas('error');

        $this->assertSame(TopupHistory::STATUS_CANCELLED, $topup->refresh()->status);
        $this->assertEquals(100.0, $this->balance($user));
    }

    public function test_a_completed_top_up_cannot_be_cancelled_by_replaying_the_cancel_url()
    {
        $user = $this->commuter(100);
        $topup = $this->startTopup($user, 250);
        $this->signedWebhook('payment.paid', 'pi_topup_1');

        $this->actingAs($user)->get(route('payment.topup.cancelled', ['ref' => $topup->transaction_id]));

        $this->assertSame(TopupHistory::STATUS_COMPLETED, $topup->refresh()->status);
        $this->assertEquals(350.0, $this->balance($user));
    }

    public function test_every_state_change_of_a_top_up_reaches_the_audit_trail()
    {
        $user = $this->commuter(100);
        $topup = $this->startTopup($user, 250);
        $this->signedWebhook('payment.paid', 'pi_topup_1');

        $entries = \Spatie\Activitylog\Models\Activity::where('subject_type', TopupHistory::class)
            ->where('subject_id', $topup->id)->orderBy('id')->get();

        $this->assertGreaterThanOrEqual(2, $entries->count());            // created (pending), then completed
        $this->assertSame('payment', $entries->first()->log_name);
        $this->assertSame('completed', $entries->last()->properties['attributes']['status']);
        $this->assertSame('pending', $entries->last()->properties['old']['status']);
    }

    // ── Reconciliation and expiry ────────────────────────────────────────

    /**
     * @group NFR-REL-18
     * @group NFR-REL
     */
    public function test_reconcile_settles_and_fails_pending_top_ups_the_webhook_missed()
    {
        $paid = $this->commuter(100);
        $declined = $this->commuter(100);
        $waiting = $this->commuter(100);

        $a = $this->startTopupWith($paid, 'pi_paid', 'cs_paid');
        $b = $this->startTopupWith($declined, 'pi_declined', 'cs_declined');
        $c = $this->startTopupWith($waiting, 'pi_waiting', 'cs_waiting');
        TopupHistory::whereIn('id', [$a->id, $b->id, $c->id])->update(['created_at' => now()->subMinutes(30)]);

        Http::swap(new Factory());
        Http::fake([
            'https://api.paymongo.com/v1/payment_intents/pi_paid' => Http::response(['data' => ['id' => 'pi_paid', 'attributes' => ['status' => 'succeeded']]]),
            'https://api.paymongo.com/v1/payment_intents/pi_declined' => Http::response(['data' => ['id' => 'pi_declined', 'attributes' => ['status' => 'failed']]]),
            'https://api.paymongo.com/v1/payment_intents/pi_waiting' => Http::response(['data' => ['id' => 'pi_waiting', 'attributes' => ['status' => 'awaiting_payment_method']]]),
        ]);

        $this->artisan('payments:reconcile')->assertSuccessful();

        $this->assertSame(TopupHistory::STATUS_COMPLETED, $a->refresh()->status);
        $this->assertEquals(200.0, $this->balance($paid));
        $this->assertSame(TopupHistory::STATUS_FAILED, $b->refresh()->status);
        $this->assertEquals(100.0, $this->balance($declined));
        $this->assertSame(TopupHistory::STATUS_PENDING, $c->refresh()->status);
    }

    protected function startTopupWith(User $user, string $intentId, string $sessionId): TopupHistory
    {
        $this->fakeCheckout($intentId, $sessionId);
        $this->actingAs($user)->post(route('payment.topup.process'), ['amount' => 100, 'payment-method' => 'gcash']);

        return TopupHistory::where('user_id', $user->id)->latest('id')->firstOrFail();
    }

    public function test_a_top_up_pending_for_twelve_hours_is_closed_and_the_wallet_is_untouched()
    {
        $user = $this->commuter(100);
        $topup = $this->startTopup($user, 250);
        TopupHistory::whereKey($topup->id)->update(['created_at' => now()->subHours(13)]);

        $this->artisan('payments:expire-pending')->assertSuccessful();

        $this->assertSame(TopupHistory::STATUS_CANCELLED, $topup->refresh()->status);
        $this->assertEquals(100.0, $this->balance($user));
    }

    // ── History and lists ────────────────────────────────────────────────

    public function test_the_history_shows_every_status_but_only_completed_ones_add_money()
    {
        $user = $this->commuter(100);
        $wallet = Wallet::where('user_id', $user->id)->first();
        foreach (['completed' => 111, 'pending' => 222, 'failed' => 333] as $status => $amount) {
            TopupHistory::create(['user_id' => $user->id, 'wallet_id' => $wallet->id, 'amount_added' => $amount, 'payment_method' => 'gcash', 'status' => $status]);
        }

        $html = $this->actingAs($user)->get(route('payment.topup.history'))->assertOk()->getContent();

        $this->assertStringContainsString('+₱111.00', $html);
        $this->assertStringNotContainsString('+₱222.00', $html);
        $this->assertStringNotContainsString('+₱333.00', $html);
        $this->assertStringContainsString('₱222.00', $html);
        $this->assertStringContainsString('pending', $html);
    }

    public function test_the_profile_and_commuter_detail_count_completed_top_ups_only()
    {
        $user = $this->commuter(0);
        $wallet = Wallet::where('user_id', $user->id)->first();
        TopupHistory::create(['user_id' => $user->id, 'wallet_id' => $wallet->id, 'amount_added' => 100, 'payment_method' => 'gcash', 'status' => 'completed']);
        TopupHistory::create(['user_id' => $user->id, 'wallet_id' => $wallet->id, 'amount_added' => 900, 'payment_method' => 'gcash', 'status' => 'pending']);

        $admin = User::factory()->create()->assignRole('admin');
        $admin->markEmailAsVerified();
        $detail = $this->actingAs($admin)->get(route('commuters.show', $user))->assertOk();
        $this->assertCount(1, $detail->viewData('topups'));
        $this->assertEquals(100.0, $detail->viewData('totalToppedUp'));
    }
}
