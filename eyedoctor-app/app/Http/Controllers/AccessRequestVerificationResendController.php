<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResendAccessRequestVerificationRequest;
use App\Models\AccessRequest;
use App\Services\AccessRequestVerificationService;
use App\Services\TurnstileVerifier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class AccessRequestVerificationResendController extends Controller
{
    private const COOLDOWN_MINUTES = 15;

    public function create(): View
    {
        return view(
            'auth.access-request-resend-verification'
        );
    }

    public function store(
        ResendAccessRequestVerificationRequest $request,
        AccessRequestVerificationService $verificationService,
        TurnstileVerifier $turnstileVerifier
    ): RedirectResponse {
        $validated = $request->validated();

        if (
            ! $turnstileVerifier->verify(
                $validated['cf-turnstile-response']
                    ?? null,
                $request->ip()
            )
        ) {
            return back()
                ->withInput(
                    $request->safe()->only('email')
                )
                ->withErrors([
                    'cf-turnstile-response' =>
                        'Security verification failed. Please try again.',
                ]);
        }

        try {
            $accessRequest =
                AccessRequest::query()
                    ->where(
                        'email_normalized',
                        $validated['email']
                    )
                    ->first();

            /*
             * Always return the same public response.
             *
             * This prevents the endpoint from revealing whether an email
             * belongs to a RETINA account or access request.
             */
            if (
                $accessRequest === null
                || ! $accessRequest->isEmailPending()
            ) {
                return $this->genericResponse();
            }

            $lastSentAt =
                $accessRequest
                    ->email_verification_sent_at;

            if (
                $lastSentAt !== null
                && $lastSentAt->greaterThan(
                    now()->subMinutes(
                        self::COOLDOWN_MINUTES
                    )
                )
            ) {
                return $this->genericResponse();
            }

            $verificationService->send(
                $accessRequest
            );
        } catch (Throwable $exception) {
            Log::error(
                'Professional access verification resend could not be completed.',
                [
                    'exception_class' =>
                        $exception::class,
                ]
            );
        }

        return $this->genericResponse();
    }

    private function genericResponse(): RedirectResponse
    {
        return redirect()
            ->route(
                'access-request.resend-verification.create'
            )
            ->with(
                'status',
                'If an email-pending professional access request exists for that address and is eligible for another message, RETINA has processed the resend request.'
            );
    }
}
