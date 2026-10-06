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
        // Base URL of the page that opens your Vue app (the same address you
        // type in the browser to see the login page). Set FRONTEND_URL in .env
        // to change it without editing this file.
        $base = rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/');

        $pageUrl = $base . '/verify-email-change/' . $this->token;

        return $this->subject('Confirm your new email address — FoundationLink')
            ->view('emails.foundation-email-change-verification')
            ->with([
                'user' => $this->user,
                'newEmail' => $this->newEmail,
                'confirmUrl' => $pageUrl . '?action=confirm',
                'cancelUrl' => $pageUrl . '?action=cancel',
            ]);
    }
}