<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Fare;
use App\Models\FareRate;
use App\Models\Payment;
use App\Models\QrCode;
use App\Models\QrScan;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Pay on board: the Admin generates QR codes, and a commuter has to scan one of them before a fare can
 * be paid (UCN_SC_E006). The rule is off in the rest of the suite (phpunit.xml); these tests switch it on.
 */
class QrRideVerificationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'commuter', 'driver', 'driver_manager', 'maintenance_manager'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        config(['qr.required' => true, 'qr.valid_minutes' => 15]);
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $fare = Fare::factory()->create();
        FareRate::factory()->create(['fare_id' => $fare->id, 'km' => 1, 'regular' => 15, 'discount' => 12]);
        FareRate::factory()->create(['fare_id' => $fare->id, 'km' => 5, 'regular' => 17.25, 'discount' => 13.75]);
    }

    protected function user(string $role): User
    {
        return User::factory()->create(['email_verified_at' => now()])->assignRole($role);
    }

    protected function commuter(float $balance = 500): User
    {
        $user = $this->user('commuter');
        Wallet::factory()->create(['user_id' => $user->id, 'balance' => (string) $balance]);

        return $user->fresh();
    }

    protected function trip(): array
    {
        return ['pickup' => 'Minglanilla', 'destination' => 'IT Park', 'distance' => '5.2', 'price-regular' => '18'];
    }

    protected function pay(User $user, string $tx = '#SC-QR000001')
    {
        return $this->actingAs($user)->post(route('payment.process'), [
            'pickup' => 'Minglanilla', 'destination' => 'IT Park', 'distance' => '5.2', 'amount' => 18,
            'payment-method' => 'Wallet', 'transaction-id' => $tx,
        ]);
    }

    // ───────────── the Admin's QR page ─────────────

    public function test_one_click_generates_a_qr_code_the_admin_can_print_switch_off_and_delete()
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)->get(route('qr-codes.index'))->assertOk()->assertSee('Generate QR Code');

        // No label, no vehicle: just a new code.
        $this->actingAs($admin)->post(route('qr-codes.store'))->assertSessionHas('success');
        $code = QrCode::latest('id')->firstOrFail();
        $this->assertSame(40, strlen($code->token));
        $this->assertTrue($code->is_active);
        $this->assertSame($admin->id, $code->created_by);

        $this->actingAs($admin)->post(route('qr-codes.store'));
        $this->assertNotSame($code->token, QrCode::latest('id')->first()->token);

        $this->actingAs($admin)->get(route('qr-codes.index'))->assertSee($code->scanUrl(), false)->assertSee('Code #'.$code->id);
        $this->actingAs($admin)->get(route('qr-codes.show', $code))->assertOk()->assertSee('Scan to pay your fare')->assertSee($code->scanUrl(), false);

        $this->actingAs($admin)->patch(route('qr-codes.toggle', $code))->assertSessionHas('success');
        $this->assertFalse($code->fresh()->is_active);

        $this->actingAs($admin)->delete(route('qr-codes.destroy', $code))->assertSessionHas('success');
        $this->assertNull(QrCode::find($code->id));
    }

    public function test_the_seeder_makes_one_working_code_and_is_safe_to_rerun()
    {
        QrCode::where('token', \Database\Seeders\QrCodeSeeder::TOKEN)->delete();

        $this->seed(\Database\Seeders\QrCodeSeeder::class);
        QrCode::where('token', \Database\Seeders\QrCodeSeeder::TOKEN)->update(['is_active' => false]);
        $this->seed(\Database\Seeders\QrCodeSeeder::class);

        $code = QrCode::where('token', \Database\Seeders\QrCodeSeeder::TOKEN)->get();
        $this->assertCount(1, $code);
        $this->assertTrue($code->first()->is_active);

        // And a commuter can actually use it.
        $commuter = $this->commuter();
        $this->actingAs($commuter)->post(route('payment.scan.verify'), ['code' => \Database\Seeders\QrCodeSeeder::TOKEN])->assertSessionHas('success');
    }

    public function test_only_the_admin_manages_qr_codes()
    {
        $code = QrCode::create([]);

        foreach (['commuter', 'driver', 'driver_manager', 'maintenance_manager'] as $role) {
            $user = $this->user($role);
            $this->actingAs($user)->get(route('qr-codes.index'))->assertStatus(403);
            $this->actingAs($user)->post(route('qr-codes.store'))->assertStatus(403);
            $this->actingAs($user)->get(route('qr-codes.show', $code))->assertStatus(403);
            $this->actingAs($user)->patch(route('qr-codes.toggle', $code))->assertStatus(403);
            $this->actingAs($user)->delete(route('qr-codes.destroy', $code))->assertStatus(403);
        }
        $this->assertTrue($code->fresh()->is_active);
    }

    public function test_the_admin_menu_links_to_the_qr_codes()
    {
        $this->assertContains('qr-codes.index', collect(config('menu.menus.admin'))->pluck('route')->filter()->all());
    }

    // ───────────── buying a ride needs a scan ─────────────

    public function test_buying_a_ride_without_a_scan_sends_the_commuter_to_scan_and_keeps_the_trip()
    {
        $commuter = $this->commuter();

        $this->actingAs($commuter)->get(route('payment.index', $this->trip()))
            ->assertRedirect(route('payment.scan'))
            ->assertSessionHas('warning')
            ->assertSessionHas('qr_trip.destination', 'IT Park');

        $this->actingAs($commuter)->get(route('payment.scan'))->assertOk()->assertSee('Scan the QR code');
    }

    public function test_scanning_a_system_qr_code_opens_the_checkout_for_the_same_trip()
    {
        $commuter = $this->commuter();
        $code = QrCode::create([]);

        $this->actingAs($commuter)->get(route('payment.index', $this->trip()));

        // What the in-app scanner reads is the link inside the code.
        $this->actingAs($commuter)->post(route('payment.scan.verify'), ['code' => $code->scanUrl()])
            ->assertRedirect(route('payment.index', $this->trip()))
            ->assertSessionHas('success');

        $this->assertNotNull(QrScan::currentFor($commuter->id));

        $this->actingAs($commuter)->get(route('payment.index', $this->trip()))
            ->assertOk()
            ->assertSee('Verified on board');
    }

    public function test_the_bare_token_works_as_typed_input_and_any_active_code_will_do()
    {
        $commuter = $this->commuter();
        QrCode::create([]);
        $other = QrCode::create([]);

        $this->actingAs($commuter)->post(route('payment.scan.verify'), ['code' => $other->token])->assertSessionHas('success');

        $this->assertSame($other->id, QrScan::currentFor($commuter->id)->qr_code_id);
    }

    public function test_a_phones_own_camera_app_opens_the_link_and_verifies()
    {
        $commuter = $this->commuter();
        $code = QrCode::create([]);

        $this->actingAs($commuter)->get($code->scanUrl())->assertRedirect(route('map'))->assertSessionHas('success');
        $this->assertNotNull(QrScan::currentFor($commuter->id));

        // Signed out, the link sends them to sign in first.
        auth()->logout();
        $this->get($code->scanUrl())->assertRedirect(route('login'));
    }

    public function test_a_qr_code_the_system_did_not_make_is_refused()
    {
        $commuter = $this->commuter();

        foreach (['https://example.com/some/other/qr', str_repeat('a', 40), 'hello', '   '] as $bad) {
            $this->actingAs($commuter)->post(route('payment.scan.verify'), ['code' => $bad])->assertSessionHasErrors('code');
        }
        $this->assertNull(QrScan::currentFor($commuter->id));
    }

    public function test_a_switched_off_or_deleted_code_is_refused()
    {
        $commuter = $this->commuter();
        $off = QrCode::create(['is_active' => false]);
        $gone = QrCode::create([]);
        $url = $gone->scanUrl();
        $gone->delete();

        $this->actingAs($commuter)->post(route('payment.scan.verify'), ['code' => $off->scanUrl()])->assertSessionHasErrors('code');
        $this->actingAs($commuter)->post(route('payment.scan.verify'), ['code' => $url])->assertSessionHasErrors('code');
        $this->actingAs($commuter)->get($off->scanUrl())->assertRedirect(route('payment.scan'))->assertSessionHas('error');
    }

    // ───────────── paying needs a scan, and spends it ─────────────

    public function test_a_fare_cannot_be_paid_without_a_scan()
    {
        $commuter = $this->commuter();

        $this->pay($commuter)->assertRedirect(route('payment.scan'))->assertSessionHas('error');

        $this->assertSame(0, Payment::where('paid_by', $commuter->id)->count());
        $this->assertEquals(500.0, (float) Wallet::where('user_id', $commuter->id)->value('balance'));
    }

    public function test_a_scan_covers_exactly_one_fare()
    {
        $commuter = $this->commuter();
        $code = QrCode::create([]);
        QrScan::record($commuter->id, $code);

        $this->pay($commuter, '#SC-QR000010')->assertSessionHas('success');

        $payment = Payment::where('transaction_id', '#SC-QR000010')->firstOrFail();
        $scan = QrScan::where('user_id', $commuter->id)->firstOrFail();
        $this->assertNotNull($scan->used_at);
        $this->assertSame($payment->id, $scan->payment_id);

        // A second, different fare needs a second scan.
        $this->pay($commuter, '#SC-QR000011')->assertRedirect(route('payment.scan'));
        $this->assertSame(1, Payment::where('paid_by', $commuter->id)->count());
    }

    public function test_a_replayed_payment_still_shows_its_receipt_without_a_new_scan()
    {
        $commuter = $this->commuter();
        QrScan::record($commuter->id, QrCode::create([]));

        $this->pay($commuter, '#SC-QR000020');
        $this->pay($commuter, '#SC-QR000020')->assertSessionHas('warning');

        $this->assertSame(1, Payment::where('paid_by', $commuter->id)->count());
    }

    public function test_a_stale_scan_does_not_count()
    {
        $commuter = $this->commuter();
        $scan = QrScan::record($commuter->id, QrCode::create([]));
        $scan->update(['scanned_at' => now()->subMinutes(16)]);

        $this->pay($commuter)->assertRedirect(route('payment.scan'));
        $this->actingAs($commuter)->get(route('payment.index', $this->trip()))->assertRedirect(route('payment.scan'));
    }

    public function test_another_commuters_scan_does_not_count()
    {
        $scanner = $this->commuter();
        $other = $this->commuter();
        QrScan::record($scanner->id, QrCode::create([]));

        $this->pay($other)->assertRedirect(route('payment.scan'));
    }

    public function test_the_rule_can_be_switched_off()
    {
        config(['qr.required' => false]);
        $commuter = $this->commuter();

        $this->pay($commuter)->assertSessionHas('success');
        $this->actingAs($commuter)->get(route('payment.scan'))->assertRedirect(route('map'));
    }

    public function test_deleting_a_code_keeps_the_scans_and_payments_made_with_it()
    {
        $commuter = $this->commuter();
        $code = QrCode::create([]);
        QrScan::record($commuter->id, $code);
        $this->pay($commuter, '#SC-QR000030');

        $code->delete();

        $this->assertSame(1, Payment::where('transaction_id', '#SC-QR000030')->count());
        $this->assertNull(QrScan::where('user_id', $commuter->id)->first()->qr_code_id);
    }
}
