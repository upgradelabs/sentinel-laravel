<?php

namespace UpgradeLabs\SentinelLaravel;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\ServiceProvider;

class SentinelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/sentinel.php', 'sentinel');

        $this->app->singleton(SentinelClient::class, function ($app) {
            return new SentinelClient(
                token: config('sentinel.token'),
                timeout: config('sentinel.timeout', 5),
            );
        });

        $this->app->singleton(SentinelReporter::class, function ($app) {
            return new SentinelReporter(
                $app->make(SentinelClient::class),
            );
        });

        $this->app->alias(SentinelClient::class, 'sentinel');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/sentinel.php' => $this->app->configPath('sentinel.php'),
            ], 'sentinel-config');
        }

        if ($this->isEnabled()) {
            $this->registerExceptionHandler();
        }
    }

    protected function isEnabled(): bool
    {
        if (! config('sentinel.enabled', true)) {
            return false;
        }

        if (! config('sentinel.token')) {
            return false;
        }

        $environments = config('sentinel.environments');

        if (is_array($environments) && count($environments) > 0) {
            return in_array($this->app->environment(), $environments);
        }

        return true;
    }

    protected function registerExceptionHandler(): void
    {
        $this->app->booted(function () {
            try {
                $handler = $this->app->make(ExceptionHandler::class);

                if (method_exists($handler, 'reportable')) {
                    $handler->reportable(function (\Throwable $e) {
                        $this->app->make(SentinelReporter::class)->report($e);

                        return false;
                    });
                }
            } catch (\Throwable) {
                // Silently fail if handler can't be resolved
            }
        });
    }
}
