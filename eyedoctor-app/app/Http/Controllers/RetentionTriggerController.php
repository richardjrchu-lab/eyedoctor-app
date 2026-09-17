<?php

namespace App\Http\Controllers;

use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class RetentionTriggerController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        abort_unless(
            app()->environment('production'),
            404
        );

        $expectedSecret = config(
            'services.retention.trigger_secret'
        );

        $providedSecret = $request->header(
            'X-Retina-Retention-Secret'
        );

        if (
            ! is_string($expectedSecret)
            || $expectedSecret === ''
            || ! is_string($providedSecret)
            || $providedSecret === ''
            || ! hash_equals(
                $expectedSecret,
                $providedSecret
            )
        ) {
            abort(404);
        }

        $validated = $request->validate([
            'dry_run' => [
                'sometimes',
                'boolean',
            ],
        ]);

        $dryRun = (bool) (
            $validated['dry_run'] ?? false
        );

        $parameters = [
            '--limit' => 50,
        ];

        if ($dryRun) {
            $parameters['--dry-run'] = true;
        }

        $exitCode = Artisan::call(
            'retina:purge-expired',
            $parameters
        );

        if ($exitCode !== Command::SUCCESS) {
            return response()->json(
                [
                    'ok' => false,
                    'status' => 'command_failed',
                ],
                500
            );
        }

        $output = Artisan::output();

        $matched = preg_match(
            '/eligible=(\d+)\s+purged=(\d+)\s+already_missing=(\d+)\s+failed=(\d+)\s+dry_run=(yes|no)/',
            $output,
            $matches
        );

        if ($matched !== 1) {
            return response()->json(
                [
                    'ok' => false,
                    'status' => 'unexpected_command_output',
                ],
                500
            );
        }

        return response()->json([
            'ok' => true,
            'status' => 'completed',
            'eligible' => (int) $matches[1],
            'purged' => (int) $matches[2],
            'already_missing' => (int) $matches[3],
            'failed' => (int) $matches[4],
            'dry_run' => $matches[5] === 'yes',
        ]);
    }
}
