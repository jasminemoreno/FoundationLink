<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FoundationEmailChangeVerification extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $token;
    public string $newEmail;

    public function __construct(User $user, string $token)
    {
        $this->user = $user;
        $this->token = $token;
        $this->newEmail = $user->pending_email;
    }

    public function build()
    {
        // NOTE: hardcoded to match the frontend-URL pattern already used
        // by the password-reset flow elsewhere in this project. If that
        // flow reads its base URL from config/env instead, point this
        // at the same source so there's only one place to change it.
        $verifyUrl = 'http://localhost:5173/verify-email-change/' . $this->token;

        return $this->subject('Confirm your new email address — FoundationLink')
            ->view('emails.foundation-email-change-verification')
            ->with([
                'user' => $this->user,
                'verifyUrl' => $verifyUrl,
                'newEmail' => $this->newEmail,
            ]);
    }
}