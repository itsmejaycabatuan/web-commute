<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * The standard signed-link verification email plus a 6-digit code for people who cannot use the link.
 */
class VerifyEmailWithCode extends VerifyEmail
{
    public function __construct(public string $code)
    {
    }

    protected function buildMailMessage($url)
    {
        $minutes = (int) config('auth.verification.expire', 60);

        return (new MailMessage)
            ->subject('Verify Email Address')
            ->line('Please click the button below to verify your email address.')
            ->action('Verify Email Address', $url)
            ->line('Cannot open the link? Enter this 6-digit code on the verification page instead:')
            ->line('**'.$this->code.'**')
            ->line("The link and the code expire in {$minutes} minutes. If you request another email, only its code works.")
            ->line('If you did not create an account, no further action is required.');
    }
}
