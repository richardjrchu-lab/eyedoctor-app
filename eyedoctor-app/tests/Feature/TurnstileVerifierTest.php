<?php

use App\Services\TurnstileVerifier;
use Illuminate\Support\Facades\Http;

function turnstileVerifierConfigure(?string $expectedHostname): void
{
    config([
        'turnstile.site_key' => 'production-site-key',
        'turnstile.secret_key' => 'production-secret-key',
        'turnstile.expected_hostname' => $expectedHostname,
        'turnstile.expected_action' => 'professional_access_request',
    ]);
}

function turnstileVerifierFakeSiteverify(string $hostname, bool $success = true): void
{
    // Http::fake() stacks stubs, so start from a fresh client each time.
    Http::swap(new \Illuminate\Http\Client\Factory);

    Http::fake([
        'challenges.cloudflare.com/*' => Http::response([
            'success' => $success,
            'hostname' => $hostname,
            'action' => 'professional_access_request',
        ]),
    ]);
}

test('a single configured hostname must match exactly', function () {
    turnstileVerifierConfigure('retina-screening.com');

    turnstileVerifierFakeSiteverify('retina-screening.com');
    expect(app(TurnstileVerifier::class)->verify('token'))->toBeTrue();

    turnstileVerifierFakeSiteverify('www.retina-screening.com');
    expect(app(TurnstileVerifier::class)->verify('token'))->toBeFalse();
});

test('every listed production hostname is accepted', function () {
    turnstileVerifierConfigure(
        'retina-screening.com, www.retina-screening.com,eyedoctor-app.onrender.com'
    );

    foreach ([
        'retina-screening.com',
        'www.retina-screening.com',
        'eyedoctor-app.onrender.com',
    ] as $hostname) {
        turnstileVerifierFakeSiteverify($hostname);

        expect(app(TurnstileVerifier::class)->verify('token'))->toBeTrue();
    }
});

test('hostnames outside the list are rejected without partial matching', function () {
    turnstileVerifierConfigure('retina-screening.com,www.retina-screening.com');

    foreach ([
        'evil.example',
        'retina-screening.com.evil.example',
        'sub.retina-screening.com',
        '',
    ] as $hostname) {
        turnstileVerifierFakeSiteverify($hostname);

        expect(app(TurnstileVerifier::class)->verify('token'))->toBeFalse();
    }
});

test('verification fails closed on missing token, missing secret, or unsuccessful result', function () {
    turnstileVerifierConfigure('retina-screening.com');
    turnstileVerifierFakeSiteverify('retina-screening.com');

    expect(app(TurnstileVerifier::class)->verify(''))->toBeFalse();

    turnstileVerifierFakeSiteverify('retina-screening.com', success: false);
    expect(app(TurnstileVerifier::class)->verify('token'))->toBeFalse();

    config(['turnstile.secret_key' => '']);
    turnstileVerifierFakeSiteverify('retina-screening.com');
    expect(app(TurnstileVerifier::class)->verify('token'))->toBeFalse();
});

test('verification fails closed when Cloudflare is unreachable', function () {
    turnstileVerifierConfigure('retina-screening.com');

    Http::swap(new \Illuminate\Http\Client\Factory);
    Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('timeout'));

    expect(app(TurnstileVerifier::class)->verify('token'))->toBeFalse();
});
