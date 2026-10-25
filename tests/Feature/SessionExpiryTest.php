<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\SessionExpiry;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * A user whose session timed out is sent to the login page with an explanation instead of a
 * blank "419 Page Expired" screen.
 */
class SessionExpiryTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'commuter']);

        // CSRF checking is skipped under phpunit, so a route stands in for "the token was rejected".
        Route::middleware('web')->post('/_test/csrf-rejected', function () {
            throw new TokenMismatchException('CSRF token mismatch.');
        });
    }

    private function commuter(): User
    {
        return User::factory()->create(['email_verified_at' => now()])->assignRole('commuter');
    }

    public function test_guest_who_was_signed_in_is_redirected_to_login_with_a_notice(): void
    {
        $this->withCookie(SessionExpiry::COOKIE, '1')
            ->get(route('profile'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('warning', SessionExpiry::EXPIRED_MESSAGE);
    }

    public function test_visitor_who_never_signed_in_is_redirected_without_the_expiry_notice(): void
    {
        $this->get(route('profile'))
            ->assertRedirect(route('login'))
            ->assertSessionMissing('warning');
    }

    public function test_expired_form_post_goes_to_login_instead_of_the_419_page(): void
    {
        $this->withCookie(SessionExpiry::COOKIE, '1')
            ->post('/_test/csrf-rejected')
            ->assertRedirect(route('login'))
            ->assertSessionHas('warning', SessionExpiry::EXPIRED_MESSAGE);
    }

    public function test_expired_form_post_from_a_signed_out_page_returns_to_that_page(): void
    {
        $this->from('/register')
            ->post('/_test/csrf-rejected')
            ->assertRedirect('/register')
            ->assertSessionHas('warning', SessionExpiry::PAGE_EXPIRED_MESSAGE);
    }

    public function test_signed_in_user_with_a_bad_token_still_sees_the_419_page(): void
    {
        $this->actingAs($this->commuter())
            ->post('/_test/csrf-rejected')
            ->assertStatus(419);
    }

    public function test_ajax_request_after_expiry_gets_a_401_pointing_at_the_login_page(): void
    {
        $this->withCookie(SessionExpiry::COOKIE, '1')->withCredentials()
            ->postJson('/_test/csrf-rejected')
            ->assertStatus(401)
            ->assertJson(['session_expired' => true, 'redirect' => route('login', [], false)]);

        $this->withCookie(SessionExpiry::COOKIE, '1')->withCredentials()
            ->getJson(route('session.status'))
            ->assertStatus(401)
            ->assertJson(['session_expired' => true]);
    }

    public function test_session_status_is_ok_while_signed_in(): void
    {
        $this->actingAs($this->commuter())
            ->getJson(route('session.status'))
            ->assertOk()
            ->assertJson(['active' => true]);
    }

    public function test_signing_in_sets_the_flag_and_signing_out_clears_it(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'password' => bcrypt('secret-pass')]);

        $this->post(route('users.login'), ['email' => $user->email, 'password' => 'secret-pass'])
            ->assertCookie(SessionExpiry::COOKIE, '1');

        $this->actingAs($user)->post(route('users.logout'))
            ->assertCookieExpired(SessionExpiry::COOKIE);
    }

    public function test_authenticated_pages_carry_the_idle_notice_script(): void
    {
        $user = $this->commuter();

        $this->actingAs($user)->get(route('map'))
            ->assertOk()
            ->assertSee('window.__sessionGuard', false)
            ->assertSee('/session/status', false);

        auth()->logout();
        $this->get(route('login'))->assertOk()->assertDontSee('window.__sessionGuard', false);
    }
}
