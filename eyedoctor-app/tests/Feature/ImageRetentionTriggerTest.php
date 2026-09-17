<?php

use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->app->detectEnvironment(
        fn () => 'production'
    );

    config([
        'services.retention.trigger_secret' => 'test-only-retention-secret-7N4qV9xK',
    ]);
});

afterEach(function () {
    $this->app->detectEnvironment(
        fn () => 'testing'
    );
});

test('authorized production dry run returns compact retention counts', function () {
    $this->withoutMiddleware(
        ThrottleRequests::class
    );

    $response = $this->postJson(
        '/internal/retention/purge',
        [
            'dry_run' => true,
        ],
        [
            'X-Retina-Retention-Secret' => 'test-only-retention-secret-7N4qV9xK',
        ]
    );

    $response
        ->assertOk()
        ->assertExactJson([
            'ok' => true,
            'status' => 'completed',
            'eligible' => 0,
            'purged' => 0,
            'already_missing' => 0,
            'failed' => 0,
            'dry_run' => true,
        ]);

    expect($response->getContent())
        ->not->toContain(
            'test-only-retention-secret-7N4qV9xK'
        );
});

test('retention trigger rejects a missing secret', function () {
    $this->withoutMiddleware(
        ThrottleRequests::class
    );

    Artisan::shouldReceive('call')
        ->never();

    $this->postJson(
        '/internal/retention/purge',
        [
            'dry_run' => true,
        ]
    )->assertNotFound();
});

test('retention trigger rejects an incorrect secret', function () {
    $this->withoutMiddleware(
        ThrottleRequests::class
    );

    Artisan::shouldReceive('call')
        ->never();

    $this->postJson(
        '/internal/retention/purge',
        [
            'dry_run' => true,
        ],
        [
            'X-Retina-Retention-Secret' => 'wrong-test-secret',
        ]
    )->assertNotFound();
});

test('retention trigger is unavailable outside production', function () {
    $this->withoutMiddleware(
        ThrottleRequests::class
    );

    $this->app->detectEnvironment(
        fn () => 'testing'
    );

    Artisan::shouldReceive('call')
        ->never();

    $this->postJson(
        '/internal/retention/purge',
        [
            'dry_run' => true,
        ],
        [
            'X-Retina-Retention-Secret' => 'test-only-retention-secret-7N4qV9xK',
        ]
    )->assertNotFound();
});

test('live retention trigger ignores caller supplied command and limit', function () {
    $this->withoutMiddleware(
        ThrottleRequests::class
    );

    Artisan::shouldReceive('call')
        ->once()
        ->with(
            'retina:purge-expired',
            [
                '--limit' => 50,
            ]
        )
        ->andReturn(Command::SUCCESS);

    Artisan::shouldReceive('output')
        ->once()
        ->andReturn(
            'Retention purge complete: eligible=2 purged=1 already_missing=1 failed=0 dry_run=no'
        );

    $response = $this->postJson(
        '/internal/retention/purge',
        [
            'dry_run' => false,
            'limit' => 999999,
            'command' => 'route:list',
            'arguments' => [
                '--env' => 'anything',
            ],
        ],
        [
            'X-Retina-Retention-Secret' => 'test-only-retention-secret-7N4qV9xK',
        ]
    );

    $response
        ->assertOk()
        ->assertExactJson([
            'ok' => true,
            'status' => 'completed',
            'eligible' => 2,
            'purged' => 1,
            'already_missing' => 1,
            'failed' => 0,
            'dry_run' => false,
        ]);
});

test('retention trigger returns safe failure when artisan command fails', function () {
    $this->withoutMiddleware(
        ThrottleRequests::class
    );

    Artisan::shouldReceive('call')
        ->once()
        ->with(
            'retina:purge-expired',
            [
                '--limit' => 50,
            ]
        )
        ->andReturn(Command::FAILURE);

    Artisan::shouldReceive('output')
        ->never();

    $response = $this->postJson(
        '/internal/retention/purge',
        [
            'dry_run' => false,
        ],
        [
            'X-Retina-Retention-Secret' => 'test-only-retention-secret-7N4qV9xK',
        ]
    );

    $response
        ->assertStatus(500)
        ->assertExactJson([
            'ok' => false,
            'status' => 'command_failed',
        ]);

    expect($response->getContent())
        ->not->toContain(
            'test-only-retention-secret-7N4qV9xK'
        );
});

test('retention trigger does not accept get requests', function () {
    $this->withoutMiddleware(
        ThrottleRequests::class
    );

    $this->getJson(
        '/internal/retention/purge',
        [
            'X-Retina-Retention-Secret' => 'test-only-retention-secret-7N4qV9xK',
        ]
    )->assertMethodNotAllowed();
});

test('retention trigger is rate limited', function () {
    for ($attempt = 1; $attempt <= 3; $attempt++) {
        $this->postJson(
            '/internal/retention/purge',
            [
                'dry_run' => true,
            ],
            [
                'X-Retina-Retention-Secret' => 'test-only-retention-secret-7N4qV9xK',
            ]
        )->assertOk();
    }

    $this->postJson(
        '/internal/retention/purge',
        [
            'dry_run' => true,
        ],
        [
            'X-Retina-Retention-Secret' => 'test-only-retention-secret-7N4qV9xK',
        ]
    )->assertTooManyRequests();
});
