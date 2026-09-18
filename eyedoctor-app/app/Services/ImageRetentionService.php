<?php

namespace App\Services;

use App\Models\Image;
use App\Models\RetentionPurgeLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ImageRetentionService
{
    public function purgeExpired(
        bool $dryRun = false,
        int $limit = 50
    ): array {
        $now = now();
        $batchLimit = min($limit, 50);

        $summary = [
            'eligible' => 0,
            'purged' => 0,
            'already_missing' => 0,
            'failed' => 0,
            'dry_run' => $dryRun,
        ];

        $images = Image::query()
            ->whereNull('retention_purged_at')
            ->whereNotNull('storage_path')
            ->orderBy('created_at')
            ->orderBy('id')
            ->cursor();

        foreach ($images as $image) {
            $expiresAt = $image->created_at
                ->copy()
                ->addYearNoOverflow();

            if ($expiresAt->gt($now)) {
                break;
            }

            if ($summary['eligible'] >= $batchLimit) {
                break;
            }

            $summary['eligible']++;

            if ($dryRun) {
                RetentionPurgeLog::create([
                    'image_id' => $image->id,
                    'mode' => 'dry_run',
                    'outcome' => 'eligible',
                    'message' => null,
                    'attempted_at' => $now,
                ]);

                continue;
            }

            $path = $image->storage_path;

            try {
                $disk = Storage::disk('s3');

                if (! $disk->exists($path)) {
                    DB::transaction(function () use (
                        $image,
                        $now
                    ): void {
                        $image->update([
                            'storage_path' => null,
                            'retention_purged_at' => $now,
                        ]);

                        RetentionPurgeLog::create([
                            'image_id' => $image->id,
                            'mode' => 'live',
                            'outcome' => 'already_missing',
                            'message' => null,
                            'attempted_at' => $now,
                        ]);
                    });

                    $summary['already_missing']++;

                    continue;
                }

                $disk->delete($path);

                DB::transaction(function () use (
                    $image,
                    $now
                ): void {
                    $image->update([
                        'storage_path' => null,
                        'retention_purged_at' => $now,
                    ]);

                    RetentionPurgeLog::create([
                        'image_id' => $image->id,
                        'mode' => 'live',
                        'outcome' => 'purged',
                        'message' => null,
                        'attempted_at' => $now,
                    ]);
                });

                $summary['purged']++;
            } catch (Throwable $exception) {
                RetentionPurgeLog::create([
                    'image_id' => $image->id,
                    'mode' => 'live',
                    'outcome' => 'failed',
                    'message' => $exception::class,
                    'attempted_at' => $now,
                ]);

                $summary['failed']++;
            }
        }

        return $summary;
    }
}
