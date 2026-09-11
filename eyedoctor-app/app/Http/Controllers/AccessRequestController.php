<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAccessRequestRequest;
use App\Services\AccessRequestSubmissionService;
use App\Services\AccessRequestVerificationService;
use App\Services\DatabaseConnectionRetry;
use App\Services\TurnstileVerifier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class AccessRequestController extends Controller
{
    public function __construct(
        private readonly AccessRequestVerificationService $verificationService,
        private readonly AccessRequestSubmissionService $submissionService,
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
         * Existing RETINA users and non-rejected applications remain blocked.
         *
         * A rejected application is intentionally eligible for resubmission.
         * The public response remains generic either way so this endpoint
         * cannot be used for account or application enumeration.
         */
        try {
            $emailBlocked =
                $this->databaseRetry->run(
                    fn (): bool =>
                        $this->submissionService
                            ->emailBlocksSubmission(
                                $email
                            )
                );

            if ($emailBlocked) {
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
            $submission =
                $this->submissionService->persist(
                    validated:
                        $validated,

                    email:
                        $email,

                    proof:
                        $proof,

                    proofDisk:
                        self::PROOF_DISK,

                    privacyNoticeVersion:
                        self::PRIVACY_NOTICE_VERSION,

                    appropriateUseNoticeVersion:
                        self::APPROPRIATE_USE_NOTICE_VERSION
                );

            /*
             * Another request/account may have become active between
             * preflight and persistence.
             *
             * Return the same generic response and remove the newly uploaded
             * object so no duplicate/orphaned proof remains.
             */
            if ($submission === null) {
                $this->deleteUploadedProof(
                    $disk,
                    $proof['object_key']
                );

                return $this->receivedResponse();
            }

            $accessRequest =
                $submission['access_request'];

            $replacedProofKey =
                $submission['replaced_proof_key'];
        } catch (Throwable $exception) {
            /*
             * Database persistence failed after the new proof was uploaded.
             * Remove only the newly uploaded object.
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
         * A successful rejected-request resubmission now points at the new
         * proof. Remove the old proof only AFTER the database commit succeeds.
         *
         * deleteUploadedProof() is best-effort and logs storage failures
         * without applicant PII.
         */
        if (
            is_string($replacedProofKey)
            && $replacedProofKey !== ''
            && $replacedProofKey
                !== $proof['object_key']
        ) {
            $this->deleteUploadedProof(
                $disk,
                $replacedProofKey
            );
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
