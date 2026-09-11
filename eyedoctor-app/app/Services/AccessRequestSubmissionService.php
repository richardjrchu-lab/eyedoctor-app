<?php

namespace App\Services;

use App\Models\AccessRequest;
use App\Models\AccessRequestEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class AccessRequestSubmissionService
{
    /**
     * Determine whether a public submission must be treated as already known.
     *
     * Existing RETINA users are always blocked.
     * Existing applications are blocked unless their latest state is rejected.
     *
     * The controller still returns the same generic received response so this
     * distinction is never exposed to an unauthenticated applicant.
     */
    public function emailBlocksSubmission(
        string $email
    ): bool {
        if (
            User::query()
                ->whereRaw(
                    'LOWER(email) = ?',
                    [$email]
                )
                ->exists()
        ) {
            return true;
        }

        $existingRequest =
            AccessRequest::query()
                ->where(
                    'email_normalized',
                    $email
                )
                ->first();

        return
            $existingRequest !== null
            && ! $existingRequest->isRejected();
    }

    /**
     * Persist a new submission or safely reuse one rejected application.
     *
     * Rejected resubmissions retain the same database row and append-only
     * event history. A new public_id is generated so any verification URL
     * issued for the previous submission becomes unusable immediately.
     *
     * @param array<string, mixed> $validated
     * @param array{
     *     object_key: string,
     *     mime_type: string,
     *     size_bytes: int,
     *     sha256: string,
     *     real_path: string
     * } $proof
     *
     * @return array{
     *     access_request: AccessRequest,
     *     replaced_proof_key: ?string
     * }|null
     */
    public function persist(
        array $validated,
        string $email,
        array $proof,
        string $proofDisk,
        string $privacyNoticeVersion,
        string $appropriateUseNoticeVersion
    ): ?array {
        return DB::transaction(
            function () use (
                $validated,
                $email,
                $proof,
                $proofDisk,
                $privacyNoticeVersion,
                $appropriateUseNoticeVersion
            ): ?array {
                /*
                 * Recheck the User table inside the transaction.
                 *
                 * A public applicant must never create another application
                 * for an email that already belongs to a RETINA account.
                 */
                if (
                    User::query()
                        ->whereRaw(
                            'LOWER(email) = ?',
                            [$email]
                        )
                        ->exists()
                ) {
                    return null;
                }

                /*
                 * Lock an existing application before deciding whether it is
                 * eligible for resubmission.
                 */
                $existingRequest =
                    AccessRequest::query()
                        ->where(
                            'email_normalized',
                            $email
                        )
                        ->lockForUpdate()
                        ->first();

                if ($existingRequest !== null) {
                    if (
                        ! $existingRequest->isRejected()
                        || $existingRequest->approved_user_id !== null
                    ) {
                        return null;
                    }

                    return $this->resubmitRejectedRequest(
                        $existingRequest,
                        $validated,
                        $email,
                        $proof,
                        $proofDisk,
                        $privacyNoticeVersion,
                        $appropriateUseNoticeVersion
                    );
                }

                $accessRequest =
                    $this->createNewRequest(
                        $validated,
                        $email,
                        $proof,
                        $proofDisk,
                        $privacyNoticeVersion,
                        $appropriateUseNoticeVersion
                    );

                return [
                    'access_request' =>
                        $accessRequest,

                    'replaced_proof_key' =>
                        null,
                ];
            },
            3
        );
    }

    /**
     * Create the first submission for an email address.
     *
     * @param array<string, mixed> $validated
     * @param array{
     *     object_key: string,
     *     mime_type: string,
     *     size_bytes: int,
     *     sha256: string,
     *     real_path: string
     * } $proof
     */
    private function createNewRequest(
        array $validated,
        string $email,
        array $proof,
        string $proofDisk,
        string $privacyNoticeVersion,
        string $appropriateUseNoticeVersion
    ): AccessRequest {
        $now = now();

        $licenseNumber =
            $validated['license_registration_number']
            ?? null;

        $accessRequest =
            AccessRequest::create([
                'full_name' =>
                    $validated['full_name'],

                'email' =>
                    $email,

                'profession' =>
                    $validated['profession'],

                'institution' =>
                    $validated['institution'],

                'department_position' =>
                    $validated['department_position']
                    ?? null,

                'license_registration_number' =>
                    $licenseNumber,

                'license_registration_last4' =>
                    $this->registrationLastFour(
                        $licenseNumber
                    ),

                'proof_type' =>
                    $validated['proof_type'],

                'proof_disk' =>
                    $proofDisk,

                'proof_object_key' =>
                    $proof['object_key'],

                'proof_mime_type' =>
                    $proof['mime_type'],

                'proof_size_bytes' =>
                    $proof['size_bytes'],

                'proof_sha256' =>
                    $proof['sha256'],

                'proof_uploaded_at' =>
                    $now,

                'submission_count' =>
                    1,

                'last_submitted_at' =>
                    $now,

                'privacy_consent_at' =>
                    $now,

                'privacy_notice_version' =>
                    $privacyNoticeVersion,

                'appropriate_use_consent_at' =>
                    $now,

                'appropriate_use_notice_version' =>
                    $appropriateUseNoticeVersion,
            ]);

        $accessRequest->events()->create([
            'event_type' =>
                'submitted',

            'actor_type' =>
                AccessRequestEvent::ACTOR_APPLICANT,

            'actor_user_id' =>
                null,

            'from_status' =>
                null,

            'to_status' =>
                AccessRequest::STATUS_EMAIL_PENDING,
        ]);

        return $accessRequest;
    }

    /**
     * Reset one rejected application into a fresh email-pending submission.
     *
     * The original database row and prior append-only events remain intact.
     * The previous proof key is returned so the controller can remove that
     * object only after the database transaction commits successfully.
     *
     * @param array<string, mixed> $validated
     * @param array{
     *     object_key: string,
     *     mime_type: string,
     *     size_bytes: int,
     *     sha256: string,
     *     real_path: string
     * } $proof
     *
     * @return array{
     *     access_request: AccessRequest,
     *     replaced_proof_key: string
     * }
     */
    private function resubmitRejectedRequest(
        AccessRequest $accessRequest,
        array $validated,
        string $email,
        array $proof,
        string $proofDisk,
        string $privacyNoticeVersion,
        string $appropriateUseNoticeVersion
    ): array {
        if (
            (string) $accessRequest->proof_disk
            !== $proofDisk
        ) {
            throw new RuntimeException(
                'Rejected access request uses an unexpected proof disk.'
            );
        }

        $previousProofKey =
            (string)
            $accessRequest->proof_object_key;

        $fromStatus =
            $accessRequest->status;

        $now = now();

        $licenseNumber =
            $validated['license_registration_number']
            ?? null;

        /*
         * Rotate the public identifier so every verification link generated
         * for the previous submission is invalidated.
         */
        $accessRequest->public_id =
            (string) Str::uuid();

        $accessRequest->full_name =
            $validated['full_name'];

        $accessRequest->email =
            $email;

        $accessRequest->profession =
            $validated['profession'];

        $accessRequest->institution =
            $validated['institution'];

        $accessRequest->department_position =
            $validated['department_position']
            ?? null;

        $accessRequest->license_registration_number =
            $licenseNumber;

        $accessRequest->license_registration_last4 =
            $this->registrationLastFour(
                $licenseNumber
            );

        $accessRequest->proof_type =
            $validated['proof_type'];

        $accessRequest->proof_disk =
            $proofDisk;

        $accessRequest->proof_object_key =
            $proof['object_key'];

        $accessRequest->proof_mime_type =
            $proof['mime_type'];

        $accessRequest->proof_size_bytes =
            $proof['size_bytes'];

        $accessRequest->proof_sha256 =
            $proof['sha256'];

        $accessRequest->proof_uploaded_at =
            $now;

        $accessRequest->proof_deleted_at =
            null;

        $accessRequest->submission_count =
            max(
                1,
                (int)
                $accessRequest->submission_count
            ) + 1;

        $accessRequest->last_submitted_at =
            $now;

        /*
         * A resubmission is a fresh review cycle.
         */
        $accessRequest->status =
            AccessRequest::STATUS_EMAIL_PENDING;

        $accessRequest->email_verification_sent_at =
            null;

        $accessRequest->email_verified_at =
            null;

        $accessRequest->reviewed_by =
            null;

        $accessRequest->reviewed_at =
            null;

        $accessRequest->rejection_reason =
            null;

        $accessRequest->approved_user_id =
            null;

        $accessRequest->account_setup_sent_at =
            null;

        /*
         * Record consent again under the currently displayed notices.
         */
        $accessRequest->privacy_consent_at =
            $now;

        $accessRequest->privacy_notice_version =
            $privacyNoticeVersion;

        $accessRequest->appropriate_use_consent_at =
            $now;

        $accessRequest->appropriate_use_notice_version =
            $appropriateUseNoticeVersion;

        $accessRequest->save();

        $accessRequest->events()->create([
            'event_type' =>
                'resubmitted',

            'actor_type' =>
                AccessRequestEvent::ACTOR_APPLICANT,

            'actor_user_id' =>
                null,

            'from_status' =>
                $fromStatus,

            'to_status' =>
                AccessRequest::STATUS_EMAIL_PENDING,
        ]);

        return [
            'access_request' =>
                $accessRequest,

            'replaced_proof_key' =>
                $previousProofKey,
        ];
    }

    /**
     * Generate the masked display suffix from trusted server data.
     */
    private function registrationLastFour(
        ?string $registrationNumber
    ): ?string {
        if ($registrationNumber === null) {
            return null;
        }

        $compact = preg_replace(
            '/[^A-Za-z0-9]/',
            '',
            $registrationNumber
        );

        if (
            ! is_string($compact)
            || $compact === ''
        ) {
            return null;
        }

        return substr(
            $compact,
            -4
        );
    }
}
