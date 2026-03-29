<?php

namespace UpgradeLabs\SentinelLaravel;

/**
 * Trait for use in Laravel 8/9 Exception Handlers that don't support reportable().
 *
 * Usage in app/Exceptions/Handler.php:
 *
 *     use UpgradeLabs\SentinelLaravel\ReportsToSentinel;
 *
 *     class Handler extends ExceptionHandler
 *     {
 *         use ReportsToSentinel;
 *
 *         public function register(): void
 *         {
 *             $this->reportable(function (Throwable $e) {
 *                 $this->reportToSentinel($e);
 *             });
 *         }
 *     }
 */
trait ReportsToSentinel
{
    protected function reportToSentinel(\Throwable $exception): void
    {
        if (app()->bound(SentinelReporter::class)) {
            app(SentinelReporter::class)->report($exception);
        }
    }
}
