<?php

namespace App\Http\Controllers;

use App\Http\Requests\RejectAccessRequestRequest;
use App\Http\Requests\StoreAccessRequestRequest;
use App\Models\AccessRequest;
use App\Services\AccessRequestDecisionService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AdminAccessRequestController extends Controller
{
    public function index(
        Request $request
    ): Response {
        $status = trim(
            (string) $request->query(
                'status',
                ''
            )
        );

        if (
            $status !== ''
            && ! in_array(
                $status,
                AccessRequest::STATUSES,
                true
            )
        ) {
            $status = '';
        }

        $query = AccessRequest::query()
            ->latest('created_at');

        if ($status !== '') {
            $query->where(
                'status',
                $status
            );
        }

        $accessRequests =
            $query
                ->paginate(20)
                ->withQueryString();

        $rawCounts =
            AccessRequest::query()
                ->selectRaw(
                    'status, COUNT(*) as total'
                )
                ->groupBy('status')
                ->pluck(
                    'total',
                    'status'
                );

        $counts = [];

        foreach (
            AccessRequest::STATUSES
            as $state
        ) {
            $counts[$state] =
                (int) (
                    $rawCounts[$state]
                    ?? 0
                );
        }

        $counts['all'] =
            array_sum($counts);

        return response()
            ->view(
                'admin.access-requests.index',
                [
                    'accessRequests' =>
                        $accessRequests,

                    'selectedStatus' =>
                        $status,

                    'statusLabels' =>
                        $this->statusLabels(),

                    'professionOptions' =>
                        StoreAccessRequestRequest::PROFESSION_OPTIONS,

                    'counts' =>
                        $counts,
                ]
            )
            ->header(
                'Cache-Control',
                'private, no-store, max-age=0'
            )
            ->header(
                'Pragma',
                'no-cache'
            );
    }

    public function show(
        AccessRequest $accessRequest
    ): Response {
        $accessRequest->load([
            'reviewer:id,name,email',
            'approvedUser:id,name,email',
            'events.actorUser:id,name,email',
        ]);

        $licenseNumber = null;
        $licenseUnavailable = false;

        if (
            $accessRequest
                ->license_registration_last4
            !== null
        ) {
            try {
                $licenseNumber =
                    $accessRequest
                        ->license_registration_number;
            } catch (Throwable $exception) {
                $licenseUnavailable = true;

                Log::error(
                    'Professional registration number could not be decrypted for admin review.',
                    [
                        'exception_class' =>
                            $exception::class,
                    ]
                );
            }
        }

        return response()
            ->view(
                'admin.access-requests.show',
                [
                    'accessRequest' =>
                        $accessRequest,

                    'licenseNumber' =>
                        $licenseNumber,

                    'licenseUnavailable' =>
                        $licenseUnavailable,

                    'statusLabels' =>
                        $this->statusLabels(),

                    'professionOptions' =>
                        StoreAccessRequestRequest::PROFESSION_OPTIONS,

                    'proofTypeOptions' =>
                        StoreAccessRequestRequest::PROOF_TYPE_OPTIONS,
                ]
            )
            ->header(
                'Cache-Control',
                'private, no-store, max-age=0'
            )
            ->header(
                'Pragma',
                'no-cache'
            );
    }

    public function proof(
        AccessRequest $accessRequest
    ): RedirectResponse {
        if (
            $accessRequest
                ->proof_deleted_at
            !== null
        ) {
            abort(404);
        }

        $diskName =
            (string)
            $accessRequest->proof_disk;

        $objectKey =
            (string)
            $accessRequest->proof_object_key;

        if (
            $diskName
                !== 'professional_verifications'
            || $objectKey === ''
        ) {
            abort(404);
        }

        try {
            $disk =
                Storage::disk(
                    $diskName
                );

            if (
                ! $disk->exists(
                    $objectKey
                )
            ) {
                abort(404);
            }

            $temporaryUrl =
                $disk->temporaryUrl(
                    $objectKey,
                    now()->addMinutes(5)
                );
        } catch (Throwable $exception) {
            Log::error(
                'Professional verification document could not be opened for admin review.',
                [
                    'exception_class' =>
                        $exception::class,
                ]
            );

            abort(
                503,
                'The verification document is temporarily unavailable.'
            );
        }

        return redirect()
            ->away(
                $temporaryUrl
            )
            ->withHeaders([
                'Cache-Control' =>
                    'private, no-store, max-age=0',
            ]);
    }

    public function approve(
        Request $request,
        AccessRequest $accessRequest,
        AccessRequestDecisionService $decisionService
    ): RedirectResponse {
        try {
            $result =
                $decisionService->approve(
                    $accessRequest,
                    $request->user()
                );
        } catch (DomainException $exception) {
            return back()->withErrors([
                'decision' =>
                    $exception->getMessage(),
            ]);
        }

        if (
            $result['setup_email_sent']
        ) {
            return redirect()
                ->route(
                    'admin.access-requests.show',
                    $accessRequest
                )
                ->with(
                    'status',
                    'Access request approved. A doctor account was created and the secure password-setup email was sent.'
                );
        }

        return redirect()
            ->route(
                'admin.access-requests.show',
                $accessRequest
            )
            ->with(
                'warning',
                'Access request approved and the doctor account was created, but the password-setup email could not be delivered. The account remains inaccessible because its provisional password is unknown.'
            );
    }

    public function resendSetup(
        Request $request,
        AccessRequest $accessRequest,
        AccessRequestDecisionService $decisionService
    ): RedirectResponse {
        try {
            $sent =
                $decisionService
                    ->resendAccountSetup(
                        $accessRequest,
                        $request->user()
                    );
        } catch (DomainException $exception) {
            return back()->withErrors([
                'decision' =>
                    $exception->getMessage(),
            ]);
        }

        if ($sent) {
            return redirect()
                ->route(
                    'admin.access-requests.show',
                    $accessRequest
                )
                ->with(
                    'status',
                    'A fresh password-setup email was sent to the approved doctor.'
                );
        }

        return redirect()
            ->route(
                'admin.access-requests.show',
                $accessRequest
            )
            ->with(
                'warning',
                'The password-setup email could not be delivered. The approved account remains protected and another resend can be attempted later.'
            );
    }


    public function reject(
        RejectAccessRequestRequest $request,
        AccessRequest $accessRequest,
        AccessRequestDecisionService $decisionService
    ): RedirectResponse {
        try {
            $decisionService->reject(
                $accessRequest,
                $request->user(),
                $request->validated(
                    'rejection_reason'
                )
            );
        } catch (DomainException $exception) {
            return back()->withErrors([
                'decision' =>
                    $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route(
                'admin.access-requests.show',
                $accessRequest
            )
            ->with(
                'status',
                'Access request rejected. No RETINA user account was created.'
            );
    }

    /**
     * @return array<string, string>
     */
    private function statusLabels(): array
    {
        return [
            AccessRequest::STATUS_EMAIL_PENDING =>
                'Email Pending',

            AccessRequest::STATUS_PENDING_REVIEW =>
                'Pending Review',

            AccessRequest::STATUS_APPROVED =>
                'Approved',

            AccessRequest::STATUS_REJECTED =>
                'Rejected',
        ];
    }
}
