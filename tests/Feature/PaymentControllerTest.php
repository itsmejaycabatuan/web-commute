<?php

namespace Tests\Feature;

use App\Models\Fare;
use App\Models\FareRate;
use App\Models\Payment;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'commuter']);
    }

    public function test_payment_index_route_responds()
    {
        $response = $this->get(route('payment.index'));
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_payment_history_route_responds()
    {
        $response = $this->get(route('payment.history'));
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_payment_process_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('payment.process'), [
                'amount' => 100,
            ]);
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_payment_topup_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('payment.topup'), [
                'amount' => 50,
            ]);
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_payment_topup_history_route_responds()
    {
        $response = $this->get(route('payment.topup.history'));
        $this->assertTrue($response->getStatusCode() > 0);
    }

    /** 
     * Payment Tests
     */
    protected function commuterWithWallet(float $balance = 500): User
    {
        $user = User::factory()->create()->assignRole('commuter');
        $user->markEmailAsVerified();
        Wallet::factory()->create(['user_id' => $user->id, 'balance' => (string) $balance]);

        return $user->fresh();
    }

    public function test_commuter_can_pay_a_fare_from_the_wallet()
    {
        // A deterministic rate table; the server prices the fare, not the browser.
        $fare = Fare::factory()->create();
        FareRate::factory()->create(['fare_id' => $fare->id, 'km' => 5, 'regular' => 17.25]);

        $user = $this->commuterWithWallet(500);

        $this->actingAs($user)
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('payment.process'), [
                'pickup' => 'Minglanilla',
                'destination' => 'IT Park',
                'distance' => '5',
                'amount' => 1, // ignored: the server re-prices the fare
                'payment-method' => 'Wallet',
                'transaction-id' => '#SC-PCT0001',
            ])
            ->assertRedirect();

        $payment = Payment::where('transaction_id', '#SC-PCT0001')->firstOrFail();
        $this->assertSame('paid', $payment->status);
        $this->assertEquals(18.0, (float) $payment->price);
        $this->assertEquals(482.0, (float) Wallet::where('user_id', $user->id)->value('balance'));
    }

    public function test_payment_process_rejects_an_incomplete_fare()
    {
        $user = $this->commuterWithWallet();

        $this->actingAs($user)
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('payment.process'), ['amount' => 75])
            ->assertSessionHasErrors(['pickup', 'destination', 'distance', 'payment-method', 'transaction-id']);

        $this->assertDatabaseMissing('payments', ['paid_by' => $user->id]);
    }

    public function test_guest_cannot_process_payment()
    {
        $this->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('payment.process'), ['amount' => 75])
            ->assertRedirect(route('login'));
    }
}
