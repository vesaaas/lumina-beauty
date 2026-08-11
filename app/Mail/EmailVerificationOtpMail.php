<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerificationOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verify your Lumina Beauty email',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.email-verification-otp',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
