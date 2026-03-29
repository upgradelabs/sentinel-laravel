<?php

namespace UpgradeLabs\SentinelLaravel\Facades;

use Illuminate\Support\Facades\Facade;
use UpgradeLabs\SentinelLaravel\SentinelClient;

/**
 * @method static \Illuminate\Http\Client\Response|null report(array $payload)
 * @method static bool isConfigured()
 * @method static string|null getUrl()
 *
 * @see \UpgradeLabs\SentinelLaravel\SentinelClient
 */
class Sentinel extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SentinelClient::class;
    }
}
