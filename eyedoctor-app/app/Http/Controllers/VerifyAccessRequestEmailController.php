<?php

namespace App\Http\Controllers;

use App\Models\AccessRequest;
use App\Models\AccessRequestEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class VerifyAccessRequestEmailController extends Controller
{
    /**
     * Verify the email address bound to a valid signed link.
     *
     * The "signed" route middleware verifies the URL signature and expiry
     * before this controller runs.
     */
    public function __invoke(
        string $publicId,
        string $emailHash
    ): RedirectResponse {
        $accessRequest = AccessRequest::query()
            ->where(
                'public_id',
                $publicId
            )
            ->firstOrFail();

        $expectedHash = hash(
            'sha256',
            $accessRequest->email_normalized
        );

        if (! hash_equals($expectedHash, $emailHash)) {
            throw new HttpException(
                403,
                'This verification link is invalid.'
            );
        }

        DB::transaction(
            function () use ($accessRequest): void {
                $locked = AccessRequest::query()
                    ->whereKey(
                        $accessRequest->getKey()
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                /*
                 * Verification links are idempotent.
                 *
                 * Reopening a link after successful verification must never
                 * create a duplicate event or move the request backwards.
                 */
                if (
                    ! $locked->isEmailPending()
                    || $locked->email_verified_at !== null
                ) {
                    return;
                }

                $now = now();

                $locked->email_verified_at =
                    $now;

                $locked->status =
                    AccessRequest::STATUS_PENDING_REVIEW;

                $locked->save();

                $locked->events()->create([
                    'event_type' =>
                        'email_verified',

                    'actor_type' =>
                        AccessRequestEvent::ACTOR_APPLICANT,

                    'actor_user_id' =>
                        null,

                    'from_status' =>
                        AccessRequest::STATUS_EMAIL_PENDING,

                    'to_status' =>
                        AccessRequest::STATUS_PENDING_REVIEW,
                ]);
            }
        );

        return redirect()->route(
            'access-request.email-verified'
        );
    }
}
