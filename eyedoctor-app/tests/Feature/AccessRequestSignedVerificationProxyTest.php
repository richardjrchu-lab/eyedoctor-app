<?php

use App\Models\AccessRequest;
use App\Services\AccessRequestVerificationService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

function makeProxyVerificationAccessRequest(
    string $email
): AccessRequest {
    return AccessRequest::query()->create([
        'full_name' =>
            'Proxy Verification Test Professional',

        'email' =>
            $email,

        'profession' =>
            'physician',

        'institution' =>
            'RETINA Test Medical Center',

        'department_position' =>
            'Ophthalmology',

        'proof_type' =>
            'institution_id',

        'proof_disk' =>
            'professional_verifications',

        'proof_object_key' =>
            'access-requests/test/'.
            Str::uuid().
            '.png',

        'proof_mime_type' =>
            'image/png',

        'proof_size_bytes' =>
            1024,

        'proof_sha256' =>
            hash('sha256', $email),

        'proof_uploaded_at' =>
            now(),

        'submission_count' =>
            1,

        'last_submitted_at' =>
            now(),

        'privacy_consent_at' =>
            now(),

        'privacy_notice_version' =>
            'test-v1',

        'appropriate_use_consent_at' =>
            now(),

        'appropriate_use_notice_version' =>
            'test-v1',
    ]);
}

function proxyVerificationRouteParameters(
    AccessRequest $accessRequest
): array {
    return [
        'publicId' =>
            $accessRequest->public_id,

        'emailHash' =>
            hash(
                'sha256',
                $accessRequest->email_normalized
            ),
    ];
}

function proxyVerificationHttpTransportUrl(
    string $httpsUrl
): string {
    $httpUrl = preg_replace(
        '/^https:\/\//',
        'http://',
        $httpsUrl,
        1
    );

    if (
        ! is_string($httpUrl)
        || $httpUrl === $httpsUrl
    ) {
        throw new RuntimeException(
            'Expected an HTTPS signed URL.'
        );
    }

    return $httpUrl;
}

beforeEach(function () {
    /*
     * Generate the signed URL exactly as an HTTPS production URL.
     *
     * The request itself will then arrive over simulated plain HTTP,
     * just as Render forwards HTTPS traffic to the container.
     */
    config([
        'app.url' => 'https://retina.test',
    ]);

    URL::forceRootUrl(
        'https://retina.test'
    );

    URL::forceScheme('https');
});

test(
    'professional verification accepts a valid signed url behind the Render HTTPS proxy',
    function () {
        $accessRequest =
            makeProxyVerificationAccessRequest(
                'proxy-valid@example.test'
            );

        $signedUrl =
            app(
                AccessRequestVerificationService::class
            )->verificationUrl(
                $accessRequest
            );

        /*
         * Simulate:
         *
         * Browser -> HTTPS -> Render
         * Render  -> HTTP  -> application
         *
         * Render preserves the original scheme in
         * X-Forwarded-Proto.
         */
        $response = $this
            ->withServerVariables([
                'REMOTE_ADDR' =>
                    '203.0.113.11',

                'SERVER_PORT' =>
                    80,

                'HTTPS' =>
                    'off',
            ])
            ->withHeaders([
                'X-Forwarded-Proto' =>
                    'https',
            ])
            ->get(
                proxyVerificationHttpTransportUrl(
                    $signedUrl
                )
            );

        $response->assertRedirect(
            route(
                'access-request.email-verified'
            )
        );

        $fresh =
            $accessRequest->fresh();

        expect($fresh->status)
            ->toBe(
                AccessRequest::STATUS_PENDING_REVIEW
            );

        expect($fresh->email_verified_at)
            ->not->toBeNull();

        expect(
            $fresh
                ->events()
                ->where(
                    'event_type',
                    'email_verified'
                )
                ->count()
        )->toBe(1);
    }
);

test(
    'professional verification rejects a tampered signed url behind the proxy',
    function () {
        $accessRequest =
            makeProxyVerificationAccessRequest(
                'proxy-tampered@example.test'
            );

        $signedUrl =
            app(
                AccessRequestVerificationService::class
            )->verificationUrl(
                $accessRequest
            );

        /*
         * Adding any signed parameter after generation must
         * invalidate the HMAC.
         */
        $tamperedUrl =
            $signedUrl.'&tampered=1';

        $response = $this
            ->withServerVariables([
                'REMOTE_ADDR' =>
                    '203.0.113.12',

                'SERVER_PORT' =>
                    80,

                'HTTPS' =>
                    'off',
            ])
            ->withHeaders([
                'X-Forwarded-Proto' =>
                    'https',
            ])
            ->get(
                proxyVerificationHttpTransportUrl(
                    $tamperedUrl
                )
            );

        $response->assertForbidden();

        $fresh =
            $accessRequest->fresh();

        expect($fresh->status)
            ->toBe(
                AccessRequest::STATUS_EMAIL_PENDING
            );

        expect($fresh->email_verified_at)
            ->toBeNull();

        expect(
            $fresh
                ->events()
                ->where(
                    'event_type',
                    'email_verified'
                )
                ->count()
        )->toBe(0);
    }
);

test(
    'professional verification rejects an expired signed url behind the proxy',
    function () {
        $accessRequest =
            makeProxyVerificationAccessRequest(
                'proxy-expired@example.test'
            );

        $expiredUrl =
            URL::temporarySignedRoute(
                'access-request.verify',
                now()->subMinute(),
                proxyVerificationRouteParameters(
                    $accessRequest
                )
            );

        $response = $this
            ->withServerVariables([
                'REMOTE_ADDR' =>
                    '203.0.113.13',

                'SERVER_PORT' =>
                    80,

                'HTTPS' =>
                    'off',
            ])
            ->withHeaders([
                'X-Forwarded-Proto' =>
                    'https',
            ])
            ->get(
                proxyVerificationHttpTransportUrl(
                    $expiredUrl
                )
            );

        $response->assertForbidden();

        $fresh =
            $accessRequest->fresh();

        expect($fresh->status)
            ->toBe(
                AccessRequest::STATUS_EMAIL_PENDING
            );

        expect($fresh->email_verified_at)
            ->toBeNull();

        expect(
            $fresh
                ->events()
                ->where(
                    'event_type',
                    'email_verified'
                )
                ->count()
        )->toBe(0);
    }
);