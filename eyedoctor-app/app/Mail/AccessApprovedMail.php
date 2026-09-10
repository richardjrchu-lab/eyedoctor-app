<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccessApprovedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $recipientName,
        public readonly string $setupUrl,
        public readonly int $expiresMinutes
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject:
                'Your RETINA professional access request was approved'
        );
    }

    public function content(): Content
    {
        return new Content(
            view:
                'emails.access-request-approved'
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
