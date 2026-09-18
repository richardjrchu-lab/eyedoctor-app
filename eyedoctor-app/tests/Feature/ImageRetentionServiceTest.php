<?php

use App\Models\Image;
use App\Models\RetentionPurgeLog;
use App\Models\User;
use App\Services\ImageRetentionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('image retention service exists', function () {
    expect(
        class_exists(ImageRetentionService::class)
    )->toBeTrue();
});

test('image retention service exposes purge expired interface', function () {
    $service = new ImageRetentionService;

    expect(method_exists($service, 'purgeExpired'))
        ->toBeTrue();
});

test('dry run identifies an expired image without deleting or mutating it', function () {
    Storage::fake('s3');

    $this->travelTo(
        Carbon::parse('2029-09-17 10:00:00')
    );

    $user = User::factory()->create();

    $image = Image::create([
        'user_id' => $user->id,
        'storage_path' => 'uploads/1/expired.png',
        'anonymized_filename' => 'expired.png',
        'validation_status' => 'valid',
    ]);

    $image->forceFill([
        'created_at' => now()->subYear(),
        'updated_at' => now()->subYear(),
    ])->save();

    Storage::disk('s3')->put(
        $image->storage_path,
        'test-image'
    );

    $summary = app(ImageRetentionService::class)
        ->purgeExpired(
            dryRun: true,
            limit: 50
        );

    expect($summary['eligible'])
        ->toBe(1)
        ->and($summary['purged'])
        ->toBe(0)
        ->and($summary['already_missing'])
        ->toBe(0)
        ->and($summary['failed'])
        ->toBe(0)
        ->and($summary['dry_run'])
        ->toBeTrue();

    expect(
        Storage::disk('s3')
            ->exists('uploads/1/expired.png')
    )->toBeTrue();

    $fresh = $image->fresh();

    expect($fresh->storage_path)
        ->toBe('uploads/1/expired.png')
        ->and($fresh->retention_purged_at)
        ->toBeNull();

    $this->assertDatabaseHas(
        'retention_purge_logs',
        [
            'image_id' => $image->id,
            'mode' => 'dry_run',
            'outcome' => 'eligible',
        ]
    );
});

test('live purge removes expired source image and marks record purged', function () {
    Storage::fake('s3');

    $this->travelTo(
        Carbon::parse('2029-09-17 10:00:00')
    );

    $user = User::factory()->create();

    $image = Image::create([
        'user_id' => $user->id,
        'storage_path' => 'uploads/1/live-expired.png',
        'anonymized_filename' => 'live-expired.png',
        'validation_status' => 'valid',
    ]);

    $image->forceFill([
        'created_at' => now()->subYear(),
        'updated_at' => now()->subYear(),
    ])->save();

    Storage::disk('s3')->put(
        $image->storage_path,
        'test-image'
    );

    $summary = app(ImageRetentionService::class)
        ->purgeExpired(
            dryRun: false,
            limit: 50
        );

    expect($summary['eligible'])
        ->toBe(1)
        ->and($summary['purged'])
        ->toBe(1)
        ->and($summary['already_missing'])
        ->toBe(0)
        ->and($summary['failed'])
        ->toBe(0)
        ->and($summary['dry_run'])
        ->toBeFalse();

    expect(
        Storage::disk('s3')
            ->exists('uploads/1/live-expired.png')
    )->toBeFalse();

    $fresh = $image->fresh();

    expect($fresh->storage_path)
        ->toBeNull()
        ->and($fresh->retention_purged_at)
        ->not->toBeNull();

    $this->assertDatabaseHas(
        'retention_purge_logs',
        [
            'image_id' => $image->id,
            'mode' => 'live',
            'outcome' => 'purged',
        ]
    );
});

test('live purge reconciles an expired image whose source file is already missing', function () {
    Storage::fake('s3');

    $this->travelTo(
        Carbon::parse('2029-09-17 10:00:00')
    );

    $user = User::factory()->create();

    $image = Image::create([
        'user_id' => $user->id,
        'storage_path' => 'uploads/1/already-missing.png',
        'anonymized_filename' => 'already-missing.png',
        'validation_status' => 'valid',
    ]);

    $image->forceFill([
        'created_at' => now()->subYear(),
        'updated_at' => now()->subYear(),
    ])->save();

    expect(
        Storage::disk('s3')
            ->exists('uploads/1/already-missing.png')
    )->toBeFalse();

    $summary = app(ImageRetentionService::class)
        ->purgeExpired(
            dryRun: false,
            limit: 50
        );

    expect($summary['eligible'])
        ->toBe(1)
        ->and($summary['purged'])
        ->toBe(0)
        ->and($summary['already_missing'])
        ->toBe(1)
        ->and($summary['failed'])
        ->toBe(0)
        ->and($summary['dry_run'])
        ->toBeFalse();

    $fresh = $image->fresh();

    expect($fresh->storage_path)
        ->toBeNull()
        ->and($fresh->retention_purged_at)
        ->not->toBeNull();

    $this->assertDatabaseHas(
        'retention_purge_logs',
        [
            'image_id' => $image->id,
            'mode' => 'live',
            'outcome' => 'already_missing',
        ]
    );
});

test('live purge fails closed when storage throws and preserves image state', function () {
    $this->travelTo(
        Carbon::parse('2029-09-17 10:00:00')
    );

    $user = User::factory()->create();

    $image = Image::create([
        'user_id' => $user->id,
        'storage_path' => 'uploads/1/storage-failure.png',
        'anonymized_filename' => 'storage-failure.png',
        'validation_status' => 'valid',
    ]);

    $image->forceFill([
        'created_at' => now()->subYear(),
        'updated_at' => now()->subYear(),
    ])->save();

    Storage::shouldReceive('disk')
        ->once()
        ->with('s3')
        ->andThrow(
            new RuntimeException('TOP SECRET storage backend detail')
        );

    $summary = app(ImageRetentionService::class)
        ->purgeExpired(
            dryRun: false,
            limit: 50
        );

    expect($summary['eligible'])
        ->toBe(1)
        ->and($summary['purged'])
        ->toBe(0)
        ->and($summary['already_missing'])
        ->toBe(0)
        ->and($summary['failed'])
        ->toBe(1)
        ->and($summary['dry_run'])
        ->toBeFalse();

    $fresh = $image->fresh();

    expect($fresh->storage_path)
        ->toBe('uploads/1/storage-failure.png')
        ->and($fresh->retention_purged_at)
        ->toBeNull();

    $this->assertDatabaseHas(
        'retention_purge_logs',
        [
            'image_id' => $image->id,
            'mode' => 'live',
            'outcome' => 'failed',
            'message' => RuntimeException::class,
        ]
    );

    $this->assertDatabaseMissing(
        'retention_purge_logs',
        [
            'message' => 'TOP SECRET storage backend detail',
        ]
    );
});

test('live purge caps each batch at fifty images even when a larger limit is requested', function () {
    Storage::fake('s3');

    $this->travelTo(
        Carbon::parse('2029-09-17 10:00:00')
    );

    $user = User::factory()->create();

    for ($index = 1; $index <= 51; $index++) {
        $path = sprintf(
            'uploads/%d/batch-%02d.png',
            $user->id,
            $index
        );

        $image = Image::create([
            'user_id' => $user->id,
            'storage_path' => $path,
            'anonymized_filename' => sprintf(
                'batch-%02d.png',
                $index
            ),
            'validation_status' => 'valid',
        ]);

        $image->forceFill([
            'created_at' => now()->subYear(),
            'updated_at' => now()->subYear(),
        ])->save();

        Storage::disk('s3')->put(
            $path,
            'test-image'
        );
    }

    $summary = app(ImageRetentionService::class)
        ->purgeExpired(
            dryRun: false,
            limit: 500
        );

    expect($summary['eligible'])
        ->toBe(50)
        ->and($summary['purged'])
        ->toBe(50)
        ->and($summary['already_missing'])
        ->toBe(0)
        ->and($summary['failed'])
        ->toBe(0);

    expect(
        Image::query()
            ->whereNotNull('storage_path')
            ->count()
    )->toBe(1);

    expect(
        RetentionPurgeLog::query()
            ->where('mode', 'live')
            ->where('outcome', 'purged')
            ->count()
    )->toBe(50);
});

test('retention eligibility applies to every validation status and excludes recent images', function () {
    $this->travelTo(
        Carbon::parse('2029-09-17 10:00:00')
    );

    $user = User::factory()->create();

    $expired = Image::create([
        'user_id' => $user->id,
        'storage_path' => 'uploads/1/rejected-expired.png',
        'anonymized_filename' => 'rejected-expired.png',
        'validation_status' => 'rejected_not_fundus',
    ]);

    $expired->forceFill([
        'created_at' => now()->subYear(),
        'updated_at' => now()->subYear(),
    ])->save();

    $recent = Image::create([
        'user_id' => $user->id,
        'storage_path' => 'uploads/1/error-recent.png',
        'anonymized_filename' => 'error-recent.png',
        'validation_status' => 'error',
    ]);

    $recent->forceFill([
        'created_at' => now()->subYear()->addSecond(),
        'updated_at' => now()->subYear()->addSecond(),
    ])->save();

    $summary = app(ImageRetentionService::class)
        ->purgeExpired(
            dryRun: true,
            limit: 50
        );

    expect($summary['eligible'])
        ->toBe(1);

    $this->assertDatabaseHas(
        'retention_purge_logs',
        [
            'image_id' => $expired->id,
            'mode' => 'dry_run',
            'outcome' => 'eligible',
        ]
    );

    $this->assertDatabaseMissing(
        'retention_purge_logs',
        [
            'image_id' => $recent->id,
        ]
    );

    expect($expired->fresh()->storage_path)
        ->toBe('uploads/1/rejected-expired.png')
        ->and($recent->fresh()->storage_path)
        ->toBe('uploads/1/error-recent.png');
});

test('february twenty ninth image expires on february twenty eighth next year', function () {
    $this->travelTo(
        Carbon::parse('2029-02-28 11:59:59')
    );

    $user = User::factory()->create();

    $image = Image::create([
        'user_id' => $user->id,
        'storage_path' => 'uploads/1/leap-day.png',
        'anonymized_filename' => 'leap-day.png',
        'validation_status' => 'valid',
    ]);

    $image->forceFill([
        'created_at' => Carbon::parse(
            '2028-02-29 12:00:00'
        ),
        'updated_at' => Carbon::parse(
            '2028-02-29 12:00:00'
        ),
    ])->save();

    $beforeExpiry = app(ImageRetentionService::class)
        ->purgeExpired(
            dryRun: true,
            limit: 50
        );

    expect($beforeExpiry['eligible'])
        ->toBe(0);

    $this->travelTo(
        Carbon::parse('2029-02-28 12:00:00')
    );

    $atExpiry = app(ImageRetentionService::class)
        ->purgeExpired(
            dryRun: true,
            limit: 50
        );

    expect($atExpiry['eligible'])
        ->toBe(1);

    $this->assertDatabaseHas(
        'retention_purge_logs',
        [
            'image_id' => $image->id,
            'mode' => 'dry_run',
            'outcome' => 'eligible',
        ]
    );
});

test('live purge is idempotent after an image has already been purged', function () {
    Storage::fake('s3');

    $this->travelTo(
        Carbon::parse('2029-09-17 10:00:00')
    );

    $user = User::factory()->create();

    $image = Image::create([
        'user_id' => $user->id,
        'storage_path' => 'uploads/1/idempotent.png',
        'anonymized_filename' => 'idempotent.png',
        'validation_status' => 'valid',
    ]);

    $image->forceFill([
        'created_at' => now()->subYear(),
        'updated_at' => now()->subYear(),
    ])->save();

    Storage::disk('s3')->put(
        $image->storage_path,
        'test-image'
    );

    $first = app(ImageRetentionService::class)
        ->purgeExpired(
            dryRun: false,
            limit: 50
        );

    $second = app(ImageRetentionService::class)
        ->purgeExpired(
            dryRun: false,
            limit: 50
        );

    expect($first['purged'])
        ->toBe(1)
        ->and($second['eligible'])
        ->toBe(0)
        ->and($second['purged'])
        ->toBe(0);

    expect(
        RetentionPurgeLog::query()
            ->where('image_id', $image->id)
            ->where('mode', 'live')
            ->where('outcome', 'purged')
            ->count()
    )->toBe(1);
});
