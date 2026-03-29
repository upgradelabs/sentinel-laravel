<?php

namespace UpgradeLabs\SentinelLaravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use UpgradeLabs\SentinelLaravel\SentinelClient;

class SendErrorReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int */
    public $tries = 3;

    /** @var int */
    public $backoff = 10;

    /** @var array */
    public $payload;

    /**
     * @param  array  $payload
     */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function handle(SentinelClient $client)
    {
        $client->report($this->payload);
    }

    /**
     * @param  \Throwable|null  $exception
     */
    public function failed($exception)
    {
        // Silently fail — we don't want to create infinite loops
    }
}
