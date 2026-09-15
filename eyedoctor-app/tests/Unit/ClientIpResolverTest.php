<?php

use App\Services\ClientIpResolver;
use Illuminate\Http\Request;

function clientIpTestRequest(
    string $remoteAddress,
    ?string $cloudflareIp = null
): Request {
    $server = [
        'REMOTE_ADDR' => $remoteAddress,
    ];

    if ($cloudflareIp !== null) {
        $server['HTTP_CF_CONNECTING_IP'] =
            $cloudflareIp;
    }

    return Request::create(
        '/',
        'GET',
        [],
        [],
        [],
        $server
    );
}

test(
    'client ip resolver prefers a valid Cloudflare connecting IP',
    function () {
        $request = clientIpTestRequest(
            '::1',
            '110.54.205.96'
        );

        $resolved = app(
            ClientIpResolver::class
        )->resolve($request);

        expect($resolved)
            ->toBe('110.54.205.96');
    }
);

test(
    'client ip resolver supports IPv6 visitor addresses',
    function () {
        $request = clientIpTestRequest(
            '::1',
            '2001:db8::1234'
        );

        $resolved = app(
            ClientIpResolver::class
        )->resolve($request);

        expect($resolved)
            ->toBe('2001:db8::1234');
    }
);

test(
    'client ip resolver falls back when Cloudflare header is missing',
    function () {
        $request = clientIpTestRequest(
            '192.0.2.25'
        );

        $resolved = app(
            ClientIpResolver::class
        )->resolve($request);

        expect($resolved)
            ->toBe('192.0.2.25');
    }
);

test(
    'client ip resolver rejects malformed Cloudflare values',
    function () {
        $request = clientIpTestRequest(
            '192.0.2.30',
            '198.51.100.20, 10.0.0.1'
        );

        $resolved = app(
            ClientIpResolver::class
        )->resolve($request);

        expect($resolved)
            ->toBe('192.0.2.30');
    }
);

test(
    'client ip resolver returns unknown when neither source is a valid IP',
    function () {
        $request = clientIpTestRequest(
            'not-an-ip',
            'also-not-an-ip'
        );

        $resolved = app(
            ClientIpResolver::class
        )->resolve($request);

        expect($resolved)
            ->toBe('unknown');
    }
);
