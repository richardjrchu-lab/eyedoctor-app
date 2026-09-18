<?php

use App\Models\Image;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

test('retention purge command is registered with safe options', function () {
    $commands = Artisan::all();

    expect($commands)
        ->toHaveKey('retina:purge-expired');

    $definition = $commands['retina:purge-expired']
        ->getDefinition();

    expect($definition->hasOption('dry-run'))
        ->toBeTrue()
        ->and($definition->hasOption('limit'))
        ->toBeTrue()
        ->and((string) $definition->getOption('limit')->getDefault())
        ->toBe('50');
});

test('retention command dry run reports eligible images without deleting source file', function () {
    Storage::fake('s3');

    $this->travelTo(
        Carbon::parse('2029-09-17 10:00:00')
    );

    $user = User::factory()->create();

    $image = Image::create([
        'user_id' => $user->id,
        'storage_path' => 'uploads/1/command-dry-run.png',
        'anonymized_filename' => 'command-dry-run.png',
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

    $exitCode = Artisan::call(
        'retina:purge-expired',
        [
            '--dry-run' => true,
            '--limit' => 50,
        ]
    );

    $output = Artisan::output();

    expect($exitCode)
        ->toBe(Command::SUCCESS);

    expect($output)
        ->toContain('eligible=1')
        ->toContain('purged=0')
        ->toContain('already_missing=0')
        ->toContain('failed=0')
        ->toContain('dry_run=yes');

    expect(
        Storage::disk('s3')
            ->exists('uploads/1/command-dry-run.png')
    )->toBeTrue();

    expect($image->fresh()->storage_path)
        ->toBe('uploads/1/command-dry-run.png')
        ->and($image->fresh()->retention_purged_at)
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

test('retention command performs live purge by default', function () {
    Storage::fake('s3');

    $this->travelTo(
        Carbon::parse('2029-09-17 10:00:00')
    );

    $user = User::factory()->create();

    $image = Image::create([
        'user_id' => $user->id,
        'storage_path' => 'uploads/1/command-live.png',
        'anonymized_filename' => 'command-live.png',
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

    $exitCode = Artisan::call(
        'retina:purge-expired',
        [
            '--limit' => 50,
        ]
    );

    $output = Artisan::output();

    expect($exitCode)
        ->toBe(Command::SUCCESS);

    expect($output)
        ->toContain('eligible=1')
        ->toContain('purged=1')
        ->toContain('already_missing=0')
        ->toContain('failed=0')
        ->toContain('dry_run=no');

    expect(
        Storage::disk('s3')
            ->exists('uploads/1/command-live.png')
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

test('retention command rejects a limit below one', function () {
    $exitCode = Artisan::call(
        'retina:purge-expired',
        [
            '--limit' => 0,
        ]
    );

    expect($exitCode)
        ->toBe(Command::FAILURE);

    expect(
        Artisan::output()
    )->toContain(
        'The --limit option must be at least 1.'
    );
});
