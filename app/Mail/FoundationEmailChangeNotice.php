<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FoundationEmailChangeNotice extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $newEmail;

    public function __construct(User $user, string $newEmail)
    {
        $this->user = $user;
        $this->newEmail = $newEmail;
    }

    public function build()
    {
        return $this->subject('Your FoundationLink email address was changed')
            ->view('emails.foundation-email-change-notice')
            ->with([
                'user' => $this->user,
                'newEmail' => $this->newEmail,
            ]);
    }
}