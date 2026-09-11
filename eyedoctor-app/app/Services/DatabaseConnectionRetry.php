<?php

namespace App\Services;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use PDOException;
use Throwable;

class DatabaseConnectionRetry
{
    private const MAX_ATTEMPTS = 3;

    /**
     * Execute an idempotent database operation with a small retry window
     * for transient connection-level failures.
     *
     * Do not use this to blindly replay non-idempotent writes.
     */
    public function run(callable $operation): mixed
    {
        $connection = config('database.default');

        $lastException = null;

        for (
            $attempt = 1;
            $attempt <= self::MAX_ATTEMPTS;
            $attempt++
        ) {
            try {
                return $operation();
            } catch (Throwable $exception) {
                $lastException = $exception;

                if (
                    ! $this->isTransientConnectionFailure($exception)
                    || $attempt >= self::MAX_ATTEMPTS
                ) {
                    throw $exception;
                }

                DB::purge($connection);

                usleep(
                    250000 * $attempt
                );
            }
        }

        throw $lastException;
    }

    private function isTransientConnectionFailure(
        Throwable $exception
    ): bool {
        if ($exception instanceof QueryException) {
            $sqlState =
                $exception->errorInfo[0]
                ?? null;

            if (
                is_string($sqlState)
                && str_starts_with(
                    $sqlState,
                    '08'
                )
            ) {
                return true;
            }
        }

        if ($exception instanceof PDOException) {
            if (
                str_contains(
                    $exception->getMessage(),
                    'SQLSTATE[08'
                )
            ) {
                return true;
            }
        }

        $message = mb_strtolower(
            $exception->getMessage()
        );

        return
            str_contains(
                $message,
                'could not translate host name'
            )
            || str_contains(
                $message,
                'could not connect to server'
            )
            || str_contains(
                $message,
                'connection refused'
            )
            || str_contains(
                $message,
                'server closed the connection unexpectedly'
            );
    }
}
