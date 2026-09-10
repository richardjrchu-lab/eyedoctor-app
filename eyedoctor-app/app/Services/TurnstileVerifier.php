<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TurnstileVerifier
{
    /**
     * Validate one Cloudflare Turnstile token.
     *
     * Verification fails closed:
     * missing configuration, network failures, invalid tokens,
     * hostname mismatches, and action mismatches all return false.
     */
    public function verify(
        ?string $token,
        ?string $remoteIp = null
    ): bool {
        $token = trim((string) $token);

        $secret = trim(
            (string) config(
                'turnstile.secret_key'
            )
        );

        if ($token === '' || $secret === '') {
            return false;
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
            $response = Http::asForm()
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

        $result = $response->json();

        if (
            ! is_array($result)
            || ($result['success'] ?? false) !== true
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
