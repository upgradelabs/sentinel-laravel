<?php

namespace UpgradeLabs\SentinelLaravel\Listeners;

use UpgradeLabs\SentinelLaravel\SentinelContext;

class BreadcrumbEventSubscriber
{
    public function handleQueryExecuted($event)
    {
        if (! config('sentinel.breadcrumbs.queries', true)) {
            return;
        }

        SentinelContext::breadcrumb('query', $event->sql, [
            'time_ms' => $event->time,
            'connection' => $event->connectionName,
        ]);
    }

    public function handleCacheHit($event)
    {
        if (! config('sentinel.breadcrumbs.cache', false)) {
            return;
        }

        SentinelContext::breadcrumb('cache', 'hit: ' . $event->key);
    }

    public function handleCacheMissed($event)
    {
        if (! config('sentinel.breadcrumbs.cache', false)) {
            return;
        }

        SentinelContext::breadcrumb('cache', 'miss: ' . $event->key);
    }

    public function handleJobProcessing($event)
    {
        if (! config('sentinel.breadcrumbs.jobs', true)) {
            return;
        }

        SentinelContext::breadcrumb('job', 'processing: ' . $event->job->resolveName());
    }

    public function handleMailSending($event)
    {
        SentinelContext::breadcrumb('mail', 'sending: ' . get_class($event->message));
    }

    /**
     * @param  object  $events
     * @return array
     */
    public function subscribe($events)
    {
        $listeners = [];

        $listeners['Illuminate\Database\Events\QueryExecuted'] = 'handleQueryExecuted';

        if (class_exists('Illuminate\Cache\Events\CacheHit')) {
            $listeners['Illuminate\Cache\Events\CacheHit'] = 'handleCacheHit';
            $listeners['Illuminate\Cache\Events\CacheMissed'] = 'handleCacheMissed';
        }

        if (class_exists('Illuminate\Queue\Events\JobProcessing')) {
            $listeners['Illuminate\Queue\Events\JobProcessing'] = 'handleJobProcessing';
        }

        return $listeners;
    }
}
