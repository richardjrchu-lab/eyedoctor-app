<?php

namespace App\Services;

use App\Mail\VerifyAccessRequestMail;
use App\Models\AccessRequest;
use App\Models\AccessRequestEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use RuntimeException;

class AccessRequestVerificationService
{
    /**
     * Generate a temporary signed verification URL.
     */
    public function verificationUrl(
        AccessRequest $accessRequest
    ): string {
        if (! $accessRequest->exists) {
            throw new RuntimeException(
                'Cannot generate a verification URL for an unsaved access request.'
            );
        }

        $expiresMinutes = $this->expiresMinutes();

        return URL::temporarySignedRoute(
            'access-request.verify',
            now()->addMinutes($expiresMinutes),
            [
                'publicId' =>
                    $accessRequest->public_id,

                /*
                 * The email hash does not act as the secret.
                 *
                 * Laravel's URL signature provides authenticity. This hash
                 * additionally binds the link to the email address that was
                 * verified when the URL was generated.
                 */
                'emailHash' =>
                    hash(
                        'sha256',
                        $accessRequest->email_normalized
                    ),
            ]
        );
    }

    /**
     * Send one applicant verification email.
     *
     * email_verification_sent_at is written only AFTER the mail transport
     * successfully accepts the message.
     */
    public function send(
        AccessRequest $accessRequest
    ): void {
        $accessRequest->refresh();

        if (! $accessRequest->isEmailPending()) {
            throw new RuntimeException(
                'Only email-pending access requests can receive a verification email.'
            );
        }

        $expiresMinutes = $this->expiresMinutes();

        $verificationUrl = $this->verificationUrl(
            $accessRequest
        );

        Mail::to(
            $accessRequest->email
        )->send(
            new VerifyAccessRequestMail(
                applicantName:
                    $accessRequest->full_name,

                verificationUrl:
                    $verificationUrl,

                expiresMinutes:
                    $expiresMinutes
            )
        );

        DB::transaction(
            function () use ($accessRequest): void {
                $locked = AccessRequest::query()
                    ->whereKey($accessRequest->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                /*
                 * If the link was somehow verified while delivery was in
                 * progress, do not rewrite the lifecycle state.
                 */
                if (! $locked->isEmailPending()) {
                    return;
                }

                $now = now();

                $locked->email_verification_sent_at =
                    $now;

                $locked->save();

                $locked->events()->create([
                    'event_type' =>
                        'email_verification_sent',

                    'actor_type' =>
                        AccessRequestEvent::ACTOR_SYSTEM,

                    'actor_user_id' =>
                        null,

                    'from_status' =>
                        AccessRequest::STATUS_EMAIL_PENDING,

                    'to_status' =>
                        AccessRequest::STATUS_EMAIL_PENDING,
                ]);
            }
        );
    }

    /**
     * Configured signed-link lifetime.
     */
    public function expiresMinutes(): int
    {
        $minutes = (int) config(
            'access_requests.email_verification.expires_minutes',
            1440
        );

        if ($minutes < 15 || $minutes > 10080) {
            throw new RuntimeException(
                'RETINA access-request verification expiry must be between 15 minutes and 7 days.'
            );
        }

        return $minutes;
    }
}
