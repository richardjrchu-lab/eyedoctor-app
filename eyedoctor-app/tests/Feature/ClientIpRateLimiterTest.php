<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

function renderClientIpLimiterTestRequest(
    string $cloudflareIp
): Request {
    return Request::create(
        '/',
        'GET',
        [],
        [],
        [],
        [
            'REMOTE_ADDR' => '::1',
            'HTTP_CF_CONNECTING_IP' => $cloudflareIp,
        ]
    );
}

test(
    'anonymous professional access limiters use the Cloudflare visitor IP',
    function () {
        $request =
            renderClientIpLimiterTestRequest(
                '203.0.113.40'
            );

        $names = [
            'access-request-view',
            'access-request-submit',
            'access-request-resend',
            'access-request-verify',
        ];

        foreach ($names as $name) {
            $resolver =
                RateLimiter::limiter($name);

            expect($resolver)
                ->not->toBeNull();

            $limit = $resolver($request);

            expect($limit->key)
                ->toBe('203.0.113.40');
        }
    }
);

test(
    'anonymous professional access limiters separate different visitors behind the same local proxy socket',
    function () {
        $visitorA =
            renderClientIpLimiterTestRequest(
                '203.0.113.41'
            );

        $visitorB =
            renderClientIpLimiterTestRequest(
                '198.51.100.42'
            );

        $names = [
            'access-request-view',
            'access-request-submit',
            'access-request-resend',
            'access-request-verify',
        ];

        foreach ($names as $name) {
            $resolver =
                RateLimiter::limiter($name);

            $limitA = $resolver($visitorA);
            $limitB = $resolver($visitorB);

            expect($limitA->key)
                ->toBe('203.0.113.41')
                ->not->toBe('::1');

            expect($limitB->key)
                ->toBe('198.51.100.42')
                ->not->toBe('::1');

            expect($limitA->key)
                ->not->toBe($limitB->key);
        }
    }
);
