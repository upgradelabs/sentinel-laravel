<?php

namespace UpgradeLabs\SentinelLaravel\Facades;

use Illuminate\Support\Facades\Facade;
use UpgradeLabs\SentinelLaravel\SentinelClient;

/**
 * @method static \Illuminate\Http\Client\Response|null report(array $payload)
 * @method static \Illuminate\Http\Client\Response|null testReport()
 * @method static bool isConfigured()
 *
 * @see SentinelClient
 */
class Sentinel extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SentinelClient::class;
    }
}
