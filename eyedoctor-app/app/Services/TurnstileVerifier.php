<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TurnstileVerifier
{
    /**
     * Cloudflare's public always-pass testing site key.
     *
     * This is a documented development credential, not a secret.
     */
    private const CLOUDFLARE_ALWAYS_PASS_TEST_SITE_KEY =
        '1x00000000000000000000AA';

    /**
     * Cloudflare's public always-pass testing secret.
     *
     * This is a documented development credential, not a production secret.
     */
    private const CLOUDFLARE_ALWAYS_PASS_TEST_SECRET =
        '1x0000000000000000000000000000000AA';

    /**
     * Validate one Cloudflare Turnstile token.
     *
     * Production fails closed for:
     * - missing configuration
     * - missing token
     * - network failure
     * - unsuccessful Siteverify response
     * - hostname mismatch
     * - action mismatch
     *
     * Local development using Cloudflare's exact official test credentials
     * bypasses the external Siteverify request so local E2E tests are not
     * dependent on external network availability.
     */
    public function verify(
        ?string $token,
        ?string $remoteIp = null
    ): bool {
        $token = trim(
            (string) $token
        );

        $siteKey = trim(
            (string) config(
                'turnstile.site_key'
            )
        );

        $secret = trim(
            (string) config(
                'turnstile.secret_key'
            )
        );

        if (
            $token === ''
            || $siteKey === ''
            || $secret === ''
        ) {
            return false;
        }

        /*
         * LOCAL E2E TESTING ONLY.
         *
         * This branch requires all three:
         *
         * 1. APP_ENV=local
         * 2. Cloudflare's exact official always-pass test site key
         * 3. Cloudflare's exact official always-pass test secret
         *
         * Real production credentials cannot accidentally enter this branch.
         */
        $isOfficialLocalTest =
            app()->environment('local')
            && hash_equals(
                self::CLOUDFLARE_ALWAYS_PASS_TEST_SITE_KEY,
                $siteKey
            )
            && hash_equals(
                self::CLOUDFLARE_ALWAYS_PASS_TEST_SECRET,
                $secret
            );

        if ($isOfficialLocalTest) {
            return true;
        }

        $payload = [
            'secret' =>
                $secret,

            'response' =>
                $token,
        ];

        if (
            is_string($remoteIp)
            && trim($remoteIp) !== ''
        ) {
            $payload['remoteip'] =
                trim($remoteIp);
        }

        try {
            $response =
                Http::asForm()
                    ->acceptJson()
                    ->timeout(5)
                    ->post(
                        (string) config(
                            'turnstile.verify_url'
                        ),
                        $payload
                    );
        } catch (Throwable $exception) {
            Log::warning(
                'Turnstile validation request failed.',
                [
                    'exception_class' =>
                        $exception::class,
                ]
            );

            return false;
        }

        if (! $response->successful()) {
            return false;
        }

        $result =
            $response->json();

        if (
            ! is_array($result)
            || ($result['success'] ?? false)
                !== true
        ) {
            return false;
        }

        $expectedHostname = trim(
            (string) config(
                'turnstile.expected_hostname'
            )
        );

        if (
            $expectedHostname !== ''
            && ! hash_equals(
                $expectedHostname,
                (string) (
                    $result['hostname']
                    ?? ''
                )
            )
        ) {
            return false;
        }

        $expectedAction = trim(
            (string) config(
                'turnstile.expected_action'
            )
        );

        if (
            $expectedAction !== ''
            && ! hash_equals(
                $expectedAction,
                (string) (
                    $result['action']
                    ?? ''
                )
            )
        ) {
            return false;
        }

        return true;
    }
}
