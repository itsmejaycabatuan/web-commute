<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Notifications\VerifyEmailWithCode;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * UCN_SC_E002 A2: the 6-digit code that sits next to the emailed verification link.
 */
class EmailVerificationCodeTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        Role::firstOrCreate(['name' => 'commuter']);
    }

    private function unverified(): User
    {
        return User::factory()->unverified()->create()->assignRole('commuter');
    }

    /** Send the email for real (faked transport) and hand back the plain code that was emailed. */
    private function emailedCode(User $user): string
    {
        Notification::fake();
        $user->sendEmailVerificationNotification();

        $code = null;
        Notification::assertSentTo($user, VerifyEmailWithCode::class, function ($n) use (&$code) {
            $code = $n->code;

            return true;
        });

        return $code;
    }

    private function wrongCodeFor(string $code): string
    {
        return $code === '000000' ? '111111' : '000000';
    }

    public function test_the_verification_email_carries_a_six_digit_code_and_only_its_hash_is_stored()
    {
        $user = $this->unverified();
        $code = $this->emailedCode($user);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);

        $row = EmailVerificationCode::where('user_id', $user->id)->first();
        $this->assertNotNull($row);
        $this->assertNotSame($code, $row->code_hash);
        $this->assertTrue(Hash::check($code, $row->code_hash));
        $this->assertSame(0, (int) $row->attempts);
        $this->assertTrue($row->expires_at->isFuture());
    }

    public function test_the_email_shows_both_the_link_and_the_code()
    {
        $user = $this->unverified();
        $mail = (new VerifyEmailWithCode('482913'))->toMail($user);

        $this->assertStringContainsString('482913', implode(' ', $mail->introLines).implode(' ', $mail->outroLines));
        $this->assertStringContainsString('/email/verify/'.$user->id.'/', $mail->actionUrl);
    }

    public function test_the_correct_code_verifies_the_email_and_is_audited()
    {
        $user = $this->unverified();
        $code = $this->emailedCode($user);

        $this->actingAs($user)
            ->post(route('verification.code'), ['code' => $code])
            ->assertRedirect(route('map'));

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertNull(EmailVerificationCode::where('user_id', $user->id)->first(), 'a used code is gone');

        $entry = Activity::where('event', 'email_verified')->where('subject_id', $user->id)->latest('id')->first();
        $this->assertNotNull($entry);
        $this->assertSame('code', $entry->properties['method']);
        $this->assertStringNotContainsString($code, json_encode($entry->properties));
    }

    public function test_spaces_in_the_typed_code_are_ignored()
    {
        $user = $this->unverified();
        $code = $this->emailedCode($user);

        $this->actingAs($user)
            ->post(route('verification.code'), ['code' => substr($code, 0, 3).' '.substr($code, 3)])
            ->assertRedirect(route('map'));

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_a_wrong_code_is_refused_and_counts_as_an_attempt()
    {
        $user = $this->unverified();
        $code = $this->emailedCode($user);

        $this->actingAs($user)
            ->from(route('verification.notice'))
            ->post(route('verification.code'), ['code' => $this->wrongCodeFor($code)])
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHasErrors(['code' => 'That code is incorrect.']);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $this->assertSame(1, (int) EmailVerificationCode::where('user_id', $user->id)->value('attempts'));
    }

    public function test_the_code_is_burned_after_five_wrong_guesses_even_if_the_next_one_is_right()
    {
        $user = $this->unverified();
        $code = $this->emailedCode($user);
        $wrong = $this->wrongCodeFor($code);

        for ($i = 1; $i <= 4; $i++) {
            $this->actingAs($user)->post(route('verification.code'), ['code' => $wrong])
                ->assertSessionHasErrors(['code' => 'That code is incorrect.']);
        }

        $this->actingAs($user)->post(route('verification.code'), ['code' => $wrong])
            ->assertSessionHasErrors(['code' => 'Too many incorrect attempts. Request a new code.']);

        $this->actingAs($user)->post(route('verification.code'), ['code' => $code])
            ->assertSessionHasErrors(['code' => 'There is no active code. Request a new one.']);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_an_expired_code_is_refused()
    {
        $user = $this->unverified();
        $code = $this->emailedCode($user);
        EmailVerificationCode::where('user_id', $user->id)->update(['expires_at' => now()->subMinute()]);

        $this->actingAs($user)->post(route('verification.code'), ['code' => $code])
            ->assertSessionHasErrors(['code' => 'That code has expired. Request a new one.']);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_resending_replaces_the_previous_code()
    {
        $user = $this->unverified();
        $first = $this->emailedCode($user);

        Notification::fake();
        $this->actingAs($user)->post(route('verification.send'))
            ->assertSessionHas('message', 'A new verification link and code have been sent.');
        $second = null;
        Notification::assertSentTo($user, VerifyEmailWithCode::class, function ($n) use (&$second) {
            $second = $n->code;

            return true;
        });

        $this->assertSame(1, EmailVerificationCode::where('user_id', $user->id)->count());

        if ($first !== $second) {
            $this->actingAs($user)->post(route('verification.code'), ['code' => $first])
                ->assertSessionHasErrors('code');
            $this->assertFalse($user->fresh()->hasVerifiedEmail());
        }

        $this->actingAs($user)->post(route('verification.code'), ['code' => $second])
            ->assertRedirect(route('map'));
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_the_code_must_be_six_digits()
    {
        $user = $this->unverified();
        $this->emailedCode($user);

        foreach (['', '12345', '1234567', 'abcdef', '12 34'] as $bad) {
            $this->actingAs($user)->post(route('verification.code'), ['code' => $bad])
                ->assertSessionHasErrors('code');
        }

        $this->assertSame(0, (int) EmailVerificationCode::where('user_id', $user->id)->value('attempts'),
            'malformed input is rejected before it is checked, so it costs no attempt');
    }

    public function test_the_link_still_works_and_spends_the_code()
    {
        $user = $this->unverified();
        $this->emailedCode($user);

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $this->actingAs($user)->get($url)->assertOk();

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertNull(EmailVerificationCode::where('user_id', $user->id)->first());

        $entry = Activity::where('event', 'email_verified')->where('subject_id', $user->id)->latest('id')->first();
        $this->assertSame('link', $entry->properties['method']);
    }

    public function test_a_guest_cannot_post_a_code_and_a_verified_user_is_sent_to_the_map()
    {
        $this->post(route('verification.code'), ['code' => '123456'])->assertRedirect(route('login'));

        $verified = User::factory()->create()->assignRole('commuter');
        $this->actingAs($verified)->post(route('verification.code'), ['code' => '123456'])
            ->assertRedirect(route('map'));
    }

    public function test_the_notice_page_offers_the_code_form()
    {
        $user = $this->unverified();

        $this->actingAs($user)->get(route('verification.notice'))
            ->assertOk()
            ->assertSee(route('verification.code'), false)
            ->assertSee('Verify with code');
    }

    public function test_registering_emails_a_code()
    {
        Notification::fake();

        $this->post(route('users.register'), [
            'email' => 'code.signup@example.com',
            'password' => 'password123',
            'confirm-password' => 'password123',
            'terms' => 'on',
        ])->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'code.signup@example.com')->firstOrFail();
        Notification::assertSentTo($user, VerifyEmailWithCode::class);
        $this->assertNotNull(EmailVerificationCode::where('user_id', $user->id)->first());
    }
}
