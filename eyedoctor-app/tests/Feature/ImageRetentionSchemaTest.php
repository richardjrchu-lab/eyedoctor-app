<?php

use App\Models\Image;
use App\Models\RetentionPurgeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('retention schema exposes image purge state and audit storage', function () {
    expect(
        Schema::hasColumn('images', 'retention_purged_at')
    )->toBeTrue();

    expect(
        Schema::hasTable('retention_purge_logs')
    )->toBeTrue();
});

test('image model persists and casts retention purge timestamp', function () {
    $user = User::factory()->create();

    $purgedAt = now()->subMinute();

    $image = Image::create([
        'user_id' => $user->id,
        'storage_path' => null,
        'anonymized_filename' => 'anonymousimage_retention_test.png',
        'validation_status' => 'valid',
        'retention_purged_at' => $purgedAt,
    ]);

    $fresh = $image->fresh();

    expect($fresh->retention_purged_at)
        ->not->toBeNull()
        ->and($fresh->retention_purged_at)
        ->toBeInstanceOf(Carbon::class);
});

test('retention purge log model exists', function () {
    expect(class_exists(RetentionPurgeLog::class))
        ->toBeTrue();
});

test('retention purge log defines auditable fields and attempted at datetime cast', function () {
    $model = new RetentionPurgeLog;

    expect($model->getFillable())
        ->toBe([
            'image_id',
            'mode',
            'outcome',
            'message',
            'attempted_at',
        ]);

    expect($model->getCasts())
        ->toHaveKey('attempted_at', 'datetime');
});
