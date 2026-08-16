<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Signup email verification. Mail is a stub for now: the default mailer is
 * the log driver — swap MAIL_MAILER when a real provider is configured.
 */
class VerifyEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public string $plainToken)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verify your email to start your Binnii free trial',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verify-email',
        );
    }
}
