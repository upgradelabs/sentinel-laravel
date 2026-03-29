<?php

namespace UpgradeLabs\SentinelLaravel\Middleware;

use Closure;
use UpgradeLabs\SentinelLaravel\SentinelContext;

class TrackSentinelPerformance
{
    public function handle($request, Closure $next)
    {
        $start = microtime(true);
        $memStart = memory_get_usage(true);

        SentinelContext::breadcrumb('request', $request->method().' '.$request->path());

        $response = $next($request);

        $duration = round((microtime(true) - $start) * 1000, 2);
        $memPeak = round(memory_get_peak_usage(true) / 1024 / 1024, 2);

        SentinelContext::set([
            'performance' => [
                'duration_ms' => $duration,
                'memory_peak_mb' => $memPeak,
                'memory_start_mb' => round($memStart / 1024 / 1024, 2),
            ],
        ]);

        return $response;
    }
}
