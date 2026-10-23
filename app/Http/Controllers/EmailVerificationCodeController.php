<?php

namespace App\Http\Controllers;

use App\Models\EmailVerificationCode;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;

class EmailVerificationCodeController extends Controller
{
    private const MESSAGES = [
        EmailVerificationCode::INVALID => 'That code is incorrect.',
        EmailVerificationCode::EXPIRED => 'That code has expired. Request a new one.',
        EmailVerificationCode::LOCKED => 'Too many incorrect attempts. Request a new code.',
        EmailVerificationCode::MISSING => 'There is no active code. Request a new one.',
    ];

    /**
     * The link in the email. It is signed and carries the user's id and an email hash, so it works
     * without being signed in (people open it on their phone, or in another browser) and ends on a
     * plain "email verified, you can close this page" screen.
     */
    public function link(Request $request, string $id, string $hash)
    {
        $user = User::findOrFail($id);

        abort_unless(hash_equals(sha1($user->getEmailForVerification()), $hash), 403);

        $alreadyVerified = $user->hasVerifiedEmail();

        if (! $alreadyVerified && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return view('auth.email-verified', ['alreadyVerified' => $alreadyVerified]);
    }

    /** UCN_SC_E002 A2: verify the email with the 6-digit code instead of the link. */
    public function verify(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('map');
        }

        // People type "123 456" or paste it with spaces.
        $request->merge(['code' => preg_replace('/\s+/', '', (string) $request->input('code'))]);

        $request->validate([
            'code' => ['required', 'digits:'.EmailVerificationCode::LENGTH],
        ], [
            'code.required' => 'Enter the 6-digit code from the email.',
            'code.digits' => 'The code must be 6 digits.',
        ]);

        $result = EmailVerificationCode::attempt($user, $request->input('code'));

        if ($result !== EmailVerificationCode::OK) {
            return back()->withErrors(['code' => self::MESSAGES[$result]]);
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        return redirect()->route('map')->with('success', 'Email verified!');
    }
}
