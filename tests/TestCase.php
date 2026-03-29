<?php

namespace UpgradeLabs\SentinelLaravel\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use UpgradeLabs\SentinelLaravel\SentinelServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            SentinelServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('sentinel.url', 'https://sentinel.test');
        $app['config']->set('sentinel.token', 'test-token');
        $app['config']->set('sentinel.enabled', true);
    }
}
