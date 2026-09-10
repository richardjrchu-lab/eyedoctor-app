<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyAccessRequestMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $applicantName,
        public readonly string $verificationUrl,
        public readonly int $expiresMinutes
    ) {
    }

    /**
     * Email metadata.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verify your RETINA professional access request'
        );
    }

    /**
     * Email body.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.access-request-verify'
        );
    }

    /**
     * No attachments are ever included.
     *
     * Verification documents remain in private object storage and are never
     * attached to applicant email.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
