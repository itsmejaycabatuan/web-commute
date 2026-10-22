<?php

namespace App\Http\Controllers;

use App\Models\EmailVerificationCode;
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
