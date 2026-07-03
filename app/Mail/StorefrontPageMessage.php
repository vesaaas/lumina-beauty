<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StorefrontPageMessage extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $page,
        public string $messageSubject,
        public array $attributes,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [
                new Address($this->attributes['email'], $this->attributes['name']),
            ],
            subject: $this->messageSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.storefront-page-message',
            with: [
                'page' => $this->page,
                'attributes' => $this->attributes,
            ],
        );
    }
}
