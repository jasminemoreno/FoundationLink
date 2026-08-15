<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FoundationVerificationSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public $foundation;

    /**
     * Create a new message instance.
     */
    public function __construct($foundation)
    {
        $this->foundation = $foundation;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('New Foundation Verification Request')
            ->view('emails.foundation-verification');
    }
}