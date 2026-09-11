<?php

use App\Models\AccessRequest;
use App\Models\AccessRequestEvent;
use App\Models\User;
use App\Services\AccessRequestSubmissionService;

function accessRequestSubmissionData(
    string $email = 'professional@example.test'
): array {
    return [
        'full_name' =>
            'Test Professional',

        'email' =>
            $email,

        'profession' =>
            'physician',

        'institution' =>
            'Test Medical Center',

        'department_position' =>
            'Clinical Department',

        'license_registration_number' =>
            null,

        'proof_type' =>
            'institution_id',

        'privacy_consent' =>
            '1',

        'appropriate_use_consent' =>
            '1',
    ];
}

function accessRequestProof(
    string $objectKey
): array {
    return [
        'object_key' =>
            $objectKey,

        'mime_type' =>
            'image/png',

        'size_bytes' =>
            1024,

        'sha256' =>
            str_repeat('a', 64),

        'real_path' =>
            __FILE__,
    ];
}

test(
    'a first submission creates an email pending access request',
    function () {
        $service =
            app(
                AccessRequestSubmissionService::class
            );

        $result =
            $service->persist(
                validated:
                    accessRequestSubmissionData(),

                email:
                    'professional@example.test',

                proof:
                    accessRequestProof(
                        'access-requests/test/first.png'
                    ),

                proofDisk:
                    'professional_verifications',

                privacyNoticeVersion:
                    'test-v1',

                appropriateUseNoticeVersion:
                    'test-v1'
            );

        expect($result)
            ->not->toBeNull();

        $request =
            $result['access_request']
                ->fresh();

        expect($request->status)
            ->toBe(
                AccessRequest::STATUS_EMAIL_PENDING
            );

        expect($request->submission_count)
            ->toBe(1);

        expect($request->proof_object_key)
            ->toBe(
                'access-requests/test/first.png'
            );

        expect(
            $result['replaced_proof_key']
        )->toBeNull();

        expect(
            $request
                ->events()
                ->where(
                    'event_type',
                    'submitted'
                )
                ->count()
        )->toBe(1);
    }
);

test(
    'a rejected request can be resubmitted safely',
    function () {
        $service =
            app(
                AccessRequestSubmissionService::class
            );

        $email =
            'resubmit@example.test';

        $first =
            $service->persist(
                validated:
                    accessRequestSubmissionData(
                        $email
                    ),

                email:
                    $email,

                proof:
                    accessRequestProof(
                        'access-requests/test/old.png'
                    ),

                proofDisk:
                    'professional_verifications',

                privacyNoticeVersion:
                    'test-v1',

                appropriateUseNoticeVersion:
                    'test-v1'
            );

        $request =
            $first['access_request']
                ->fresh();

        $originalId =
            $request->id;

        $originalPublicId =
            $request->public_id;

        $request->status =
            AccessRequest::STATUS_REJECTED;

        $request->email_verified_at =
            now();

        $request->reviewed_at =
            now();

        $request->rejection_reason =
            'Previous verification document was insufficient.';

        $request->save();

        $request->events()->create([
            'event_type' =>
                'rejected',

            'actor_type' =>
                AccessRequestEvent::ACTOR_SYSTEM,

            'actor_user_id' =>
                null,

            'from_status' =>
                AccessRequest::STATUS_PENDING_REVIEW,

            'to_status' =>
                AccessRequest::STATUS_REJECTED,
        ]);

        $newData =
            accessRequestSubmissionData(
                $email
            );

        $newData['full_name'] =
            'Updated Professional';

        $newData['institution'] =
            'Updated Medical Center';

        $second =
            $service->persist(
                validated:
                    $newData,

                email:
                    $email,

                proof:
                    accessRequestProof(
                        'access-requests/test/new.png'
                    ),

                proofDisk:
                    'professional_verifications',

                privacyNoticeVersion:
                    'test-v2',

                appropriateUseNoticeVersion:
                    'test-v2'
            );

        expect($second)
            ->not->toBeNull();

        $resubmitted =
            $second['access_request']
                ->fresh();

        expect($resubmitted->id)
            ->toBe($originalId);

        expect($resubmitted->public_id)
            ->not->toBe($originalPublicId);

        expect($resubmitted->status)
            ->toBe(
                AccessRequest::STATUS_EMAIL_PENDING
            );

        expect($resubmitted->submission_count)
            ->toBe(2);

        expect($resubmitted->full_name)
            ->toBe(
                'Updated Professional'
            );

        expect($resubmitted->institution)
            ->toBe(
                'Updated Medical Center'
            );

        expect($resubmitted->proof_object_key)
            ->toBe(
                'access-requests/test/new.png'
            );

        expect(
            $second['replaced_proof_key']
        )->toBe(
            'access-requests/test/old.png'
        );

        expect($resubmitted->email_verified_at)
            ->toBeNull();

        expect($resubmitted->reviewed_by)
            ->toBeNull();

        expect($resubmitted->reviewed_at)
            ->toBeNull();

        expect($resubmitted->approved_user_id)
            ->toBeNull();

        expect($resubmitted->account_setup_sent_at)
            ->toBeNull();

        $event =
            $resubmitted
                ->events()
                ->where(
                    'event_type',
                    'resubmitted'
                )
                ->first();

        expect($event)
            ->not->toBeNull();

        expect($event->from_status)
            ->toBe(
                AccessRequest::STATUS_REJECTED
            );

        expect($event->to_status)
            ->toBe(
                AccessRequest::STATUS_EMAIL_PENDING
            );
    }
);

test(
    'a non rejected existing request cannot be submitted again',
    function () {
        $service =
            app(
                AccessRequestSubmissionService::class
            );

        $email =
            'pending@example.test';

        $first =
            $service->persist(
                validated:
                    accessRequestSubmissionData(
                        $email
                    ),

                email:
                    $email,

                proof:
                    accessRequestProof(
                        'access-requests/test/pending-old.png'
                    ),

                proofDisk:
                    'professional_verifications',

                privacyNoticeVersion:
                    'test-v1',

                appropriateUseNoticeVersion:
                    'test-v1'
            );

        expect(
            $service->emailBlocksSubmission(
                $email
            )
        )->toBeTrue();

        $second =
            $service->persist(
                validated:
                    accessRequestSubmissionData(
                        $email
                    ),

                email:
                    $email,

                proof:
                    accessRequestProof(
                        'access-requests/test/pending-new.png'
                    ),

                proofDisk:
                    'professional_verifications',

                privacyNoticeVersion:
                    'test-v1',

                appropriateUseNoticeVersion:
                    'test-v1'
            );

        expect($second)
            ->toBeNull();

        $request =
            $first['access_request']
                ->fresh();

        expect($request->submission_count)
            ->toBe(1);

        expect($request->proof_object_key)
            ->toBe(
                'access-requests/test/pending-old.png'
            );
    }
);

test(
    'rejected application is eligible unless a user already exists',
    function () {
        $service =
            app(
                AccessRequestSubmissionService::class
            );

        $email =
            'rejected@example.test';

        $result =
            $service->persist(
                validated:
                    accessRequestSubmissionData(
                        $email
                    ),

                email:
                    $email,

                proof:
                    accessRequestProof(
                        'access-requests/test/rejected.png'
                    ),

                proofDisk:
                    'professional_verifications',

                privacyNoticeVersion:
                    'test-v1',

                appropriateUseNoticeVersion:
                    'test-v1'
            );

        $request =
            $result['access_request']
                ->fresh();

        $request->status =
            AccessRequest::STATUS_REJECTED;

        $request->save();

        expect(
            $service->emailBlocksSubmission(
                $email
            )
        )->toBeFalse();

        User::factory()->create([
            'email' =>
                $email,
        ]);

        expect(
            $service->emailBlocksSubmission(
                $email
            )
        )->toBeTrue();
    }
);
