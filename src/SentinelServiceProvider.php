<?php

namespace UpgradeLabs\SentinelLaravel;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\ServiceProvider;

class SentinelServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/sentinel.php', 'sentinel');

        $this->app->singleton(SentinelClient::class, function ($app) {
            return new SentinelClient(
                config('sentinel.token'),
                config('sentinel.timeout', 5)
            );
        });

        $this->app->singleton(SentinelReporter::class, function ($app) {
            return new SentinelReporter(
                $app->make(SentinelClient::class)
            );
        });

        $this->app->alias(SentinelClient::class, 'sentinel');
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/sentinel.php' => $this->app->configPath('sentinel.php'),
            ], 'sentinel-config');

            $this->commands([
                \UpgradeLabs\SentinelLaravel\Commands\SentinelDeployCommand::class,
            ]);
        }

        if ($this->isEnabled()) {
            $this->registerExceptionHandler();
            $this->registerHeartbeat();
            $this->registerBreadcrumbs();
        }
    }

    /**
     * @return bool
     */
    protected function isEnabled()
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

    protected function registerHeartbeat()
    {
        if (! config('sentinel.heartbeat', true)) {
            return;
        }

        $this->app->booted(function () {
            if ($this->app->bound(Schedule::class)) {
                $schedule = $this->app->make(Schedule::class);
                $schedule->call(function () {
                    try {
                        $this->app->make(SentinelClient::class)->heartbeat();
                    } catch (\Throwable $e) {
                        // Silent fail
                    }
                })->everyFiveMinutes()->name('sentinel:heartbeat')->withoutOverlapping();
            }
        });
    }

    protected function registerBreadcrumbs()
    {
        if (! config('sentinel.breadcrumbs.enabled', true)) {
            return;
        }

        try {
            $this->app->make('events')->subscribe(
                new \UpgradeLabs\SentinelLaravel\Listeners\BreadcrumbEventSubscriber
            );
        } catch (\Throwable $e) {
            // Silent fail
        }
    }

    protected function registerExceptionHandler()
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
            } catch (\Throwable $e) {
                // Silently fail
            }
        });
    }
}
