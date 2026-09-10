<?php

namespace App\Services;

use App\Mail\AccessApprovedMail;
use App\Models\AccessRequest;
use App\Models\AccessRequestEvent;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Throwable;

class AccessRequestDecisionService
{
    /**
     * Approve an email-verified request and create exactly one
     * doctor account.
     *
     * @return array{
     *     user: User,
     *     setup_email_sent: bool
     * }
     */
    public function approve(
        AccessRequest $accessRequest,
        User $administrator
    ): array {
        if (! $administrator->hasRole('admin')) {
            throw new DomainException(
                'Only administrators can review access requests.'
            );
        }

        $result = DB::transaction(
            function () use (
                $accessRequest,
                $administrator
            ): array {
                $lockedRequest =
                    AccessRequest::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $accessRequest->id
                        );

                if (
                    ! $lockedRequest
                        ->isPendingReview()
                ) {
                    throw new DomainException(
                        'Only requests pending review can be approved.'
                    );
                }

                if (
                    $lockedRequest
                        ->email_verified_at
                    === null
                ) {
                    throw new DomainException(
                        'The applicant email must be verified before approval.'
                    );
                }

                if (
                    $lockedRequest
                        ->approved_user_id
                    !== null
                ) {
                    throw new DomainException(
                        'This request already has an associated RETINA account.'
                    );
                }

                $normalizedEmail =
                    mb_strtolower(
                        trim(
                            (string)
                            $lockedRequest->email
                        )
                    );

                $existingUser =
                    User::query()
                        ->whereRaw(
                            'LOWER(email) = ?',
                            [$normalizedEmail]
                        )
                        ->first();

                if ($existingUser !== null) {
                    throw new DomainException(
                        'A RETINA account already exists for this email address.'
                    );
                }

                /*
                 * The applicant never supplies an authorization role.
                 * The doctor role is assigned exclusively by trusted
                 * approval code.
                 */
                $user = new User();

                $user->name =
                    $lockedRequest->full_name;

                $user->email =
                    $normalizedEmail;

                /*
                 * The applicant never receives or knows this random
                 * provisional password. The User model hashes it before
                 * storage. The account is activated by setting a new
                 * password through Laravel's reset-token mechanism.
                 */
                $user->password =
                    Str::random(64);

                /*
                 * Applicant email ownership was already proven through
                 * the separate signed access-request verification flow.
                 */
                $user->email_verified_at =
                    $lockedRequest
                        ->email_verified_at;

                $user->save();

                $user->assignRole('doctor');

                $fromStatus =
                    $lockedRequest->status;

                $lockedRequest->status =
                    AccessRequest::STATUS_APPROVED;

                $lockedRequest->reviewed_by =
                    $administrator->id;

                $lockedRequest->reviewed_at =
                    now();

                $lockedRequest->rejection_reason =
                    null;

                $lockedRequest->approved_user_id =
                    $user->id;

                $lockedRequest->save();

                AccessRequestEvent::create([
                    'access_request_id' =>
                        $lockedRequest->id,

                    'event_type' =>
                        'approved',

                    'actor_type' =>
                        AccessRequestEvent::ACTOR_ADMIN,

                    'actor_user_id' =>
                        $administrator->id,

                    'from_status' =>
                        $fromStatus,

                    'to_status' =>
                        AccessRequest::STATUS_APPROVED,
                ]);

                return [
                    'request_id' =>
                        $lockedRequest->id,

                    'user' =>
                        $user,
                ];
            },
            3
        );

        $setupEmailSent =
            $this->sendAccountSetup(
                $result['request_id'],
                $result['user']
            );

        return [
            'user' =>
                $result['user'],

            'setup_email_sent' =>
                $setupEmailSent,
        ];
    }

    /**
     * Reject an email-verified request.
     */
    public function reject(
        AccessRequest $accessRequest,
        User $administrator,
        string $reason
    ): void {
        if (! $administrator->hasRole('admin')) {
            throw new DomainException(
                'Only administrators can review access requests.'
            );
        }

        DB::transaction(
            function () use (
                $accessRequest,
                $administrator,
                $reason
            ): void {
                $lockedRequest =
                    AccessRequest::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $accessRequest->id
                        );

                if (
                    ! $lockedRequest
                        ->isPendingReview()
                ) {
                    throw new DomainException(
                        'Only requests pending review can be rejected.'
                    );
                }

                if (
                    $lockedRequest
                        ->email_verified_at
                    === null
                ) {
                    throw new DomainException(
                        'The applicant email must be verified before rejection.'
                    );
                }

                $fromStatus =
                    $lockedRequest->status;

                $lockedRequest->status =
                    AccessRequest::STATUS_REJECTED;

                $lockedRequest->reviewed_by =
                    $administrator->id;

                $lockedRequest->reviewed_at =
                    now();

                $lockedRequest->rejection_reason =
                    trim($reason);

                $lockedRequest->save();

                AccessRequestEvent::create([
                    'access_request_id' =>
                        $lockedRequest->id,

                    'event_type' =>
                        'rejected',

                    'actor_type' =>
                        AccessRequestEvent::ACTOR_ADMIN,

                    'actor_user_id' =>
                        $administrator->id,

                    'from_status' =>
                        $fromStatus,

                    'to_status' =>
                        AccessRequest::STATUS_REJECTED,
                ]);
            },
            3
        );
    }

    /**
     * Send the approved applicant a short-lived Laravel password-reset
     * token that functions as the first-time account setup invitation.
     */
    private function sendAccountSetup(
        int $accessRequestId,
        User $user
    ): bool {
        $broker =
            Password::broker();

        try {
            $token =
                $broker->createToken(
                    $user
                );

            $expiresMinutes =
                (int) config(
                    'auth.passwords.users.expire',
                    60
                );

            $setupUrl =
                route(
                    'password.reset',
                    [
                        'token' =>
                            $token,

                        'email' =>
                            $user->email,
                    ]
                );

            Mail::to(
                $user->email
            )->send(
                new AccessApprovedMail(
                    $user->name,
                    $setupUrl,
                    $expiresMinutes
                )
            );

            DB::transaction(
                function () use (
                    $accessRequestId,
                    $user
                ): void {
                    $lockedRequest =
                        AccessRequest::query()
                            ->lockForUpdate()
                            ->findOrFail(
                                $accessRequestId
                            );

                    if (
                        ! $lockedRequest
                            ->isApproved()
                        || (int)
                            $lockedRequest
                                ->approved_user_id
                            !== (int)
                            $user->id
                    ) {
                        return;
                    }

                    $lockedRequest
                        ->account_setup_sent_at =
                            now();

                    $lockedRequest->save();

                    AccessRequestEvent::create([
                        'access_request_id' =>
                            $lockedRequest->id,

                        'event_type' =>
                            'account_setup_sent',

                        'actor_type' =>
                            AccessRequestEvent::ACTOR_SYSTEM,

                        'actor_user_id' =>
                            null,

                        'from_status' =>
                            AccessRequest::STATUS_APPROVED,

                        'to_status' =>
                            AccessRequest::STATUS_APPROVED,
                    ]);
                },
                3
            );

            return true;
        } catch (Throwable $exception) {
            /*
             * Approval itself remains durable even when email delivery
             * fails. The account still has an unknown random password
             * and therefore cannot be used until a setup/reset link is
             * successfully delivered.
             */
            try {
                $broker->deleteToken(
                    $user
                );
            } catch (Throwable) {
                // Best-effort cleanup only.
            }

            Log::error(
                'Approved RETINA account setup email could not be delivered.',
                [
                    'exception_class' =>
                        $exception::class,
                ]
            );

            return false;
        }
    }
}
