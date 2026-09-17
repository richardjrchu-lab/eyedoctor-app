<?php

namespace App\Console\Commands;

use App\Services\ImageRetentionService;
use Illuminate\Console\Command;

class PurgeExpiredImages extends Command
{
    protected $signature = 'retina:purge-expired
        {--dry-run : Identify eligible images without deleting source files}
        {--limit=50 : Maximum number of eligible images to process}';

    protected $description = 'Purge expired retinal source images under the retention policy';

    public function handle(
        ImageRetentionService $retentionService
    ): int {
        $limit = (int) $this->option('limit');

        if ($limit < 1) {
            $this->error(
                'The --limit option must be at least 1.'
            );

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        $summary = $retentionService->purgeExpired(
            dryRun: $dryRun,
            limit: $limit
        );

        $this->info(sprintf(
            'Retention purge complete: eligible=%d purged=%d already_missing=%d failed=%d dry_run=%s',
            $summary['eligible'],
            $summary['purged'],
            $summary['already_missing'],
            $summary['failed'],
            $summary['dry_run'] ? 'yes' : 'no'
        ));

        return self::SUCCESS;
    }
}
