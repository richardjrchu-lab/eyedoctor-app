<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAccessRequestRequest;
use App\Models\AccessRequest;
use App\Models\AccessRequestEvent;
use App\Models\User;
use App\Services\AccessRequestVerificationService;
use App\Services\DatabaseConnectionRetry;
use App\Services\TurnstileVerifier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class AccessRequestController extends Controller
{
    public function __construct(
        private readonly AccessRequestVerificationService $verificationService,
        private readonly TurnstileVerifier $turnstileVerifier,
        private readonly DatabaseConnectionRetry $databaseRetry
    ) {
    }

    /**
     * Verification files are isolated from retinal images and APK releases.
     */
    private const PROOF_DISK = 'professional_verifications';

    /**
     * Versions stored with every consent record.
     *
     * If either notice changes materially, increment its version before
     * accepting submissions under the revised text.
     */
    private const PRIVACY_NOTICE_VERSION = '2026-09-v1';

    private const APPROPRIATE_USE_NOTICE_VERSION = '2026-09-v1';

    /**
     * Extensions are derived exclusively from server-detected MIME types.
     *
     * The browser-supplied filename and extension are never trusted.
     *
     * @var array<string, string>
     */
    private const MIME_EXTENSION_MAP = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    /**
     * Display the professional-access application.
     */
    public function create(): View
    {
        return view('auth.request-access', [
            'professionOptions' =>
                StoreAccessRequestRequest::professionOptions(),

            'proofTypeOptions' =>
                StoreAccessRequestRequest::proofTypeOptions(),

            'maximumProofSizeMb' => 8,
        ]);
    }

    /**
     * Display the intentionally generic submission confirmation.
     *
     * This page does not reveal whether an account or earlier application
     * already exists for the supplied email address.
     */
    public function received(): View
    {
        return view('auth.access-request-received');
    }

    /**
     * Store a new professional-access application.
     */
    public function store(
        StoreAccessRequestRequest $request
    ): RedirectResponse {
        $validated = $request->validated();

        /*
         * Turnstile is validated on the server before database queries,
         * verification-document processing, or object-storage uploads.
         *
         * Browser-side completion alone is never trusted.
         */
        if (
            ! $this->turnstileVerifier->verify(
                $validated['cf-turnstile-response']
                    ?? null,
                $request->ip()
            )
        ) {
            return back()->withErrors([
                'cf-turnstile-response' =>
                    'Security verification failed. Please try again.',
            ]);
        }

        $email = (string) $validated['email'];

        /*
         * Do not create duplicate applications for an existing RETINA user
         * or an email address that already has an application.
         *
         * The response is deliberately identical to a successful submission
         * so this public endpoint cannot be used for account enumeration.
         */
        try {
            $emailAlreadyKnown =
                $this->databaseRetry->run(
                    fn (): bool =>
                        $this->emailIsAlreadyKnown(
                            $email
                        )
                );

            if ($emailAlreadyKnown) {
                return $this->receivedResponse();
            }
        } catch (Throwable $exception) {
            $this->logFailure(
                'Access request preflight database check failed.',
                $exception
            );

            return $this->processingFailureResponse();
        }

        $file = $request->file('proof_document');

        if (! $file instanceof UploadedFile || ! $file->isValid()) {
            return $this->processingFailureResponse(
                'proof_document'
            );
        }

        /*
         * Compute trusted file metadata BEFORE uploading.
         *
         * No original filename is stored because it can contain unnecessary
         * personal information.
         */
        try {
            $proof = $this->prepareProofMetadata($file);
        } catch (Throwable $exception) {
            $this->logFailure(
                'Access request proof metadata preparation failed.',
                $exception
            );

            return $this->processingFailureResponse(
                'proof_document'
            );
        }

        $disk = Storage::disk(self::PROOF_DISK);

        /*
         * Upload the verification object before opening the database
         * transaction. Network/object-storage work must not hold a database
         * transaction open.
         */
        try {
            $this->uploadPrivateProof(
                $disk,
                $proof
            );
        } catch (Throwable $exception) {
            /*
             * A transport failure can occasionally leave the caller unsure
             * whether object storage accepted the upload before the response
             * was interrupted. Deletion is safe to attempt even when the
             * object was never created, so clean up defensively.
             */
            $this->deleteUploadedProof(
                $disk,
                $proof['object_key']
            );

            $this->logFailure(
                'Access request proof upload failed.',
                $exception
            );

            return $this->processingFailureResponse(
                'proof_document'
            );
        }

        /*
         * From this point onward an R2 object exists.
         *
         * Any database failure must therefore trigger compensating deletion
         * so an unreferenced verification document is not intentionally left
         * behind.
         */
        try {
            $accessRequest = DB::transaction(
                function () use (
                    $validated,
                    $email,
                    $proof
                ): ?AccessRequest {
                    /*
                     * Repeat the duplicate check inside the transaction.
                     *
                     * This narrows the race window between the initial
                     * preflight check and persistence. The database's unique
                     * normalized-email constraint remains the final authority.
                     */
                    if ($this->emailIsAlreadyKnown($email)) {
                        return null;
                    }

                    $now = now();

                    $licenseNumber =
                        $validated['license_registration_number']
                        ?? null;

                    $accessRequest = AccessRequest::create([
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

                        /*
                         * AccessRequest encrypts the full registration number
                         * using Laravel's encrypted Eloquent cast.
                         */
                        'license_registration_number' =>
                            $licenseNumber,

                        /*
                         * The browser is prohibited from setting this value.
                         * It is derived on the server only.
                         */
                        'license_registration_last4' =>
                            $this->registrationLastFour(
                                $licenseNumber
                            ),

                        'proof_type' =>
                            $validated['proof_type'],

                        'proof_disk' =>
                            self::PROOF_DISK,

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

                        /*
                         * email_verification_sent_at intentionally remains
                         * NULL until an email is actually sent.
                         */
                        'privacy_consent_at' =>
                            $now,

                        'privacy_notice_version' =>
                            self::PRIVACY_NOTICE_VERSION,

                        'appropriate_use_consent_at' =>
                            $now,

                        'appropriate_use_notice_version' =>
                            self::APPROPRIATE_USE_NOTICE_VERSION,
                    ]);

                    /*
                     * AccessRequest applies email_pending itself and validates
                     * that the lifecycle status is one of the approved states.
                     */
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
                },
                3
            );

            /*
             * Another request may have created the same application between
             * our preflight check and transaction.
             *
             * In that case no database row was created by this submission,
             * so remove its newly uploaded object and return the same generic
             * response used for successful submissions.
             */
            if ($accessRequest === null) {
                $this->deleteUploadedProof(
                    $disk,
                    $proof['object_key']
                );

                return $this->receivedResponse();
            }
        } catch (Throwable $exception) {
            /*
             * Database persistence failed after the proof was uploaded.
             * Remove the object as compensating cleanup.
             */
            $this->deleteUploadedProof(
                $disk,
                $proof['object_key']
            );

            $this->logFailure(
                'Access request database persistence failed.',
                $exception
            );

            return $this->processingFailureResponse();
        }

        /*
         * The application and its verification proof are now durably stored.
         *
         * Email delivery deliberately happens AFTER the database transaction.
         * A slow or unavailable mail provider must never hold the transaction
         * open or cause the applicant's already-accepted proof to be deleted.
         *
         * If delivery fails, the request remains email_pending. A separate
         * controlled resend workflow will provide recovery without requiring
         * the applicant to submit another verification document.
         */
        try {
            $this->verificationService->send(
                $accessRequest
            );
        } catch (Throwable $exception) {
            $this->logFailure(
                'Access request verification email delivery failed.',
                $exception
            );
        }

        return $this->receivedResponse();
    }

    /**
     * Determine whether this email already belongs to an account or request.
     *
     * The supplied email has already been normalized to lowercase by the
     * FormRequest.
     */
    private function emailIsAlreadyKnown(
        string $email
    ): bool {
        if (
            AccessRequest::query()
                ->where('email_normalized', $email)
                ->exists()
        ) {
            return true;
        }

        return User::query()
            ->whereRaw(
                'LOWER(email) = ?',
                [$email]
            )
            ->exists();
    }

    /**
     * Build trusted metadata for an uploaded verification document.
     *
     * @return array{
     *     object_key: string,
     *     mime_type: string,
     *     size_bytes: int,
     *     sha256: string,
     *     real_path: string
     * }
     */
    private function prepareProofMetadata(
        UploadedFile $file
    ): array {
        $mimeType = $file->getMimeType();

        if (
            ! is_string($mimeType)
            || ! isset(self::MIME_EXTENSION_MAP[$mimeType])
        ) {
            throw new RuntimeException(
                'Unsupported verification-document MIME type.'
            );
        }

        $realPath = $file->getRealPath();

        if (
            ! is_string($realPath)
            || ! is_file($realPath)
        ) {
            throw new RuntimeException(
                'Verification-document temporary file is unavailable.'
            );
        }

        $sizeBytes = $file->getSize();

        if (
            ! is_int($sizeBytes)
            || $sizeBytes <= 0
        ) {
            throw new RuntimeException(
                'Verification-document size could not be determined.'
            );
        }

        $sha256 = hash_file(
            'sha256',
            $realPath
        );

        if (
            ! is_string($sha256)
            || strlen($sha256) !== 64
        ) {
            throw new RuntimeException(
                'Verification-document SHA-256 calculation failed.'
            );
        }

        $extension =
            self::MIME_EXTENSION_MAP[$mimeType];

        /*
         * The storage key contains no name, email, license number,
         * institution, or original filename.
         */
        $objectKey = sprintf(
            'access-requests/%s/%s.%s',
            now()->format('Y/m'),
            Str::uuid(),
            $extension
        );

        return [
            'object_key' =>
                $objectKey,

            'mime_type' =>
                $mimeType,

            'size_bytes' =>
                $sizeBytes,

            'sha256' =>
                $sha256,

            'real_path' =>
                $realPath,
        ];
    }

    /**
     * Upload one verification document with private visibility.
     *
     * @param  mixed  $disk
     * @param  array{
     *     object_key: string,
     *     mime_type: string,
     *     size_bytes: int,
     *     sha256: string,
     *     real_path: string
     * }  $proof
     */
    private function uploadPrivateProof(
        mixed $disk,
        array $proof
    ): void {
        $stream = fopen(
            $proof['real_path'],
            'rb'
        );

        if ($stream === false) {
            throw new RuntimeException(
                'Verification-document stream could not be opened.'
            );
        }

        try {
            $written = $disk->put(
                $proof['object_key'],
                $stream,
                [
                    'visibility' =>
                        'private',

                    'ContentType' =>
                        $proof['mime_type'],
                ]
            );
        } finally {
            fclose($stream);
        }

        if ($written !== true) {
            throw new RuntimeException(
                'Verification-document upload did not complete.'
            );
        }
    }

    /**
     * Compensating cleanup for an object whose database operation failed.
     *
     * Failure to clean up is logged without applicant PII. The object key is
     * random and contains no applicant identity.
     *
     * @param  mixed  $disk
     */
    private function deleteUploadedProof(
        mixed $disk,
        string $objectKey
    ): void {
        try {
            $deleted = $disk->delete(
                $objectKey
            );

            if ($deleted !== true) {
                Log::critical(
                    'Verification proof cleanup returned false.',
                    [
                        'object_key' =>
                            $objectKey,
                    ]
                );
            }
        } catch (Throwable $exception) {
            Log::critical(
                'Verification proof cleanup failed.',
                [
                    'object_key' =>
                        $objectKey,

                    'exception_class' =>
                        $exception::class,
                ]
            );
        }
    }

    /**
     * Generate the masked-display suffix without trusting browser input.
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

    /**
     * Generic success/duplicate response.
     *
     * Never reveal whether the submitted email already exists.
     */
    private function receivedResponse(): RedirectResponse
    {
        return redirect()
            ->route(
                'access-request.received'
            );
    }

    /**
     * Generic infrastructure/storage failure.
     *
     * We deliberately do NOT call withInput(). In particular, professional
     * registration numbers must not be flashed into the session.
     */
    private function processingFailureResponse(
        string $field = 'submission'
    ): RedirectResponse {
        return back()->withErrors([
            $field =>
                'We could not securely process your access request. '
                .'Please try again.',
        ]);
    }

    /**
     * Log operational failures without applicant data or exception messages.
     *
     * Exception messages can contain query bindings or infrastructure details,
     * so only the exception class is recorded here.
     */
    private function logFailure(
        string $message,
        Throwable $exception
    ): void {
        Log::error(
            $message,
            [
                'exception_class' =>
                    $exception::class,
            ]
        );
    }
}
