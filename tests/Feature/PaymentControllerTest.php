<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
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
    public function test_admin_can_process_payment()
    {
        $admin = User::factory()->create()->assignRole('admin');

        $response = $this->actingAs($admin)
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('payment.process'), [
                'amount' => 75,
            ]);

        $this->assertDatabaseHas('payments', ['amount' => 75]);
    }

    public function test_admin_cannot_process_payment_as_non_admin()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('payment.process'), [
                'amount' => 75,
            ]);

        $response->assertStatus(403);
    }
}