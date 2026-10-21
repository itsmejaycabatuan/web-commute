<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Driver;
use App\Models\DevMarker;
use App\Models\Payment;
use App\Models\TopupHistory;
use App\Models\User;
use App\Models\UserPreference;
use App\Models\Vehicle;
use App\Models\Wallet;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Removing an account must never destroy the payment ledger: an account with payments or top-ups is
 * anonymised (personal data and access gone, financial rows kept); one without is deleted outright;
 * and the database itself refuses to cascade through payments / top-ups.
 * UCN_SC_E027, E018 A3, E012 A3, E028 A4.
 */
class AccountErasureTest extends TestCase
{
    use DatabaseTransactions;

    private int $baseline = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);

        foreach (['admin', 'driver_manager', 'maintenance_manager', 'driver', 'commuter'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $this->baseline = (int) Activity::max('id');
    }

    private function commuter(array $attributes = [], float $balance = 0): User
    {
        $user = User::factory()->create($attributes)->assignRole('commuter');
        $user->markEmailAsVerified();
        Wallet::factory()->create(['user_id' => $user->id, 'balance' => (string) $balance]);

        return $user;
    }

    private function admin(): User
    {
        return User::factory()->create()->assignRole('admin');
    }

    private function pay(User $user): Payment
    {
        return Payment::factory()->create(['paid_by' => $user->id, 'status' => 'paid']);
    }

    private function entries(?string $event = null)
    {
        return Activity::where('id', '>', $this->baseline)->when($event, fn ($q) => $q->where('event', $event))->orderBy('id')->get();
    }

    // ───────────────────────── self-deletion (E027) ─────────────────────────

    public function test_deleting_your_own_account_keeps_the_payment_history_and_erases_the_person()
    {
        $user = $this->commuter(['email' => 'leaving@example.com']);
        $payment = $this->pay($user);
        $topup = TopupHistory::factory()->create(['user_id' => $user->id, 'wallet_id' => $user->wallet->id]);
        UserPreference::create(['user_id' => $user->id, 'theme' => 'dark', 'font_size' => 12]);
        $token = $user->createToken('phone')->plainTextToken;
        DB::table('password_resets')->insert(['email' => 'leaving@example.com', 'token' => 'x', 'created_at' => now()]);

        $this->actingAs($user)->delete(route('users.delete-account'))->assertRedirect(route('login'));

        $kept = User::find($user->id);
        $this->assertNotNull($kept, 'the row stays so the ledger keeps its owner');
        $this->assertTrue($kept->isAnonymised());
        $this->assertSame("deleted-{$user->id}@deleted.invalid", $kept->email);
        $this->assertNull($kept->email_verified_at);
        $this->assertTrue($kept->is_suspended);
        $this->assertCount(0, $kept->getRoleNames());
        $this->assertFalse(Hash::check('123456789', $kept->password));

        $this->assertNotNull(Payment::find($payment->id), 'payments survive');
        $this->assertSame($user->id, (int) Payment::find($payment->id)->paid_by);
        $this->assertNotNull(TopupHistory::find($topup->id), 'top-ups survive');
        $this->assertNotNull(Wallet::where('user_id', $user->id)->first(), 'the wallet survives');

        $this->assertNull(UserPreference::where('user_id', $user->id)->first());
        $this->assertSame(0, $kept->tokens()->count());
        $this->assertFalse(DB::table('password_resets')->where('email', 'leaving@example.com')->exists());
        $this->assertNotEmpty($token);
    }

    public function test_an_account_with_no_payments_or_top_ups_is_deleted_outright()
    {
        $user = $this->commuter();
        $id = $user->id;

        $this->actingAs($user)->delete(route('users.delete-account'))->assertRedirect(route('login'));

        $this->assertNull(User::find($id));
        $this->assertNull(Wallet::where('user_id', $id)->first(), 'an empty wallet goes with the user');
    }

    public function test_self_deletion_is_refused_while_the_wallet_holds_money()
    {
        $user = $this->commuter([], 150.50);

        $this->actingAs($user)->delete(route('users.delete-account'))
            ->assertSessionHas('error');

        $this->assertNull(User::find($user->id)->anonymised_at, 'nothing was touched');
        $this->assertSame(0, $this->entries('deleted_self')->count(), 'a refused request is not an erasure');
    }

    public function test_the_audit_entry_says_how_it_ended_and_keeps_the_original_email()
    {
        $user = $this->commuter(['email' => 'trace-me@example.com']);
        $this->pay($user);

        $this->actingAs($user)->delete(route('users.delete-account'));

        $entry = $this->entries('deleted_self')->first();
        $this->assertNotNull($entry);
        $this->assertSame('anonymised', $entry->properties['outcome']);
        $this->assertSame('trace-me@example.com', $entry->properties['email']);
        $this->assertSame($user->id, $entry->causer_id);
        $this->assertCount(1, $this->entries()->where('log_name', 'account'), 'one entry, not one per layer');
    }

    // ───────────────────────── after anonymising ─────────────────────────

    public function test_an_anonymised_account_can_never_sign_in_and_the_email_can_be_registered_again()
    {
        $user = $this->commuter(['email' => 'reuse@example.com']);
        $this->pay($user);
        $this->actingAs($user)->delete(route('users.delete-account'));
        auth()->logout();

        $this->post(route('users.login'), ['email' => 'reuse@example.com', 'password' => '123456789'])
            ->assertSessionHasErrors('credentials');
        $this->post(route('users.login'), ['email' => "deleted-{$user->id}@deleted.invalid", 'password' => '123456789'])
            ->assertSessionHasErrors('credentials');

        $this->post(route('users.register'), [
            'email' => 'reuse@example.com', 'password' => 'password123',
            'confirm-password' => 'password123', 'terms' => 'on',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(2, User::whereIn('email', ['reuse@example.com', "deleted-{$user->id}@deleted.invalid"])->count());
    }

    public function test_a_driver_with_payments_loses_the_driver_profile_but_not_the_ledger()
    {
        $admin = $this->admin();
        $driver = Driver::factory()->approved()->create();
        $user = User::find($driver->user_id);
        $user->assignRole('driver');
        $payment = $this->pay($user);

        $this->actingAs($admin)->delete(route('drivers.destroy', ['driver' => $driver->id]))
            ->assertSessionHas('success');

        $this->assertNull(Driver::find($driver->id), 'the driver profile (licence data) is erased');
        $this->assertTrue(User::find($user->id)->isAnonymised());
        $this->assertNotNull(Payment::find($payment->id));
        $this->assertSame('anonymised', $this->entries('anonymised')->first()->properties['outcome']);
    }

    // ───────────────────────── admin: commuters (E018) ─────────────────────────

    public function test_an_admin_removing_a_commuter_with_payments_anonymises_them_and_says_so()
    {
        $admin = $this->admin();
        $commuter = $this->commuter(['email' => 'admin-removes@example.com']);
        $payment = $this->pay($commuter);

        $this->actingAs($admin)->delete(route('commuters.destroy', $commuter))
            ->assertSessionHas('success', 'Commuter removed. Their payments and top-ups were kept for the records.');

        $this->assertTrue(User::find($commuter->id)->isAnonymised());
        $this->assertNotNull(Payment::find($payment->id));

        $entry = $this->entries('anonymised')->first();
        $this->assertSame($admin->id, $entry->causer_id);
        $this->assertSame('admin-removes@example.com', $entry->properties['email']);
        $this->assertStringStartsWith('Anonymised commuter account', $entry->description);

        $this->actingAs($admin)->get(route('commuters.index'))->assertOk()
            ->assertDontSee("deleted-{$commuter->id}@deleted.invalid")
            ->assertDontSee('admin-removes@example.com');
    }

    public function test_a_commuter_with_only_top_ups_is_anonymised_too()
    {
        $admin = $this->admin();
        $commuter = $this->commuter();
        $topup = TopupHistory::factory()->create(['user_id' => $commuter->id, 'wallet_id' => $commuter->wallet->id]);

        $this->actingAs($admin)->delete(route('commuters.destroy', $commuter))->assertSessionHas('success');

        $this->assertTrue(User::find($commuter->id)->isAnonymised());
        $this->assertNotNull(TopupHistory::find($topup->id));
    }

    public function test_a_commuter_with_no_history_is_still_deleted_and_the_message_is_unchanged()
    {
        $admin = $this->admin();
        $commuter = $this->commuter();

        $this->actingAs($admin)->delete(route('commuters.destroy', $commuter))
            ->assertSessionHas('success', 'Commuter removed.');

        $this->assertNull(User::find($commuter->id));
        $this->assertCount(1, $this->entries('deleted'));
    }

    public function test_an_admin_still_cannot_remove_a_commuter_who_holds_money()
    {
        $commuter = $this->commuter([], 75);
        $this->pay($commuter);

        $this->actingAs($this->admin())->delete(route('commuters.destroy', $commuter))->assertSessionHas('error');

        $this->assertNull(User::find($commuter->id)->anonymised_at);
    }

    // ───────────────────────── the database safety net ─────────────────────────

    public function test_the_database_refuses_to_cascade_through_payments()
    {
        $user = $this->commuter();
        $payment = $this->pay($user);

        try {
            $user->disableLogging()->delete(); // the old, destructive path
            $this->fail('deleting a user that has payments must be refused by the database');
        } catch (QueryException $e) {
            $this->assertStringContainsString('foreign key constraint', strtolower($e->getMessage()));
        }

        $this->assertNotNull(User::find($user->id));
        $this->assertNotNull(Payment::find($payment->id));
    }

    public function test_the_database_refuses_to_cascade_through_top_ups_from_either_side()
    {
        $user = $this->commuter();
        TopupHistory::factory()->create(['user_id' => $user->id, 'wallet_id' => $user->wallet->id]);

        foreach ([fn () => $user->disableLogging()->delete(), fn () => Wallet::where('id', $user->wallet->id)->delete()] as $destroy) {
            try {
                $destroy();
                $this->fail('top-up history must not be removable by deleting its user or wallet');
            } catch (QueryException $e) {
                $this->assertStringContainsString('foreign key constraint', strtolower($e->getMessage()));
            }
        }

        $this->assertSame(1, TopupHistory::where('user_id', $user->id)->count());
    }

    public function test_everything_else_still_cascades_so_a_clean_delete_leaves_no_debris()
    {
        $user = $this->commuter();
        UserPreference::create(['user_id' => $user->id, 'theme' => 'dark', 'font_size' => 12]);
        DevMarker::factory()->create(['user_id' => $user->id]);

        $this->assertSame('deleted', $user->eraseAccount());

        $this->assertNull(UserPreference::where('user_id', $user->id)->first());
        $this->assertSame(0, DevMarker::where('user_id', $user->id)->count());
    }

    public function test_the_admin_user_count_ignores_anonymised_accounts()
    {
        $admin = $this->admin();
        $commuter = $this->commuter();
        $this->pay($commuter);
        $before = User::whereNull('anonymised_at')->count();

        $commuter->eraseAccount();

        $this->assertSame($before - 1, User::whereNull('anonymised_at')->count());
        $this->assertSame(1, User::whereNotNull('anonymised_at')->where('id', $commuter->id)->count());
    }
}
