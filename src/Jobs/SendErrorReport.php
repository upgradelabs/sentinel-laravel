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

    public int $tries = 3;

    public int $backoff = 10;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public array $payload,
    ) {}

    public function handle(SentinelClient $client): void
    {
        $client->report($this->payload);
    }

    /**
     * Determine if the job should fail silently.
     */
    public function failed(?\Throwable $exception): void
    {
        // Silently fail — we don't want to create infinite loops
        // by reporting Sentinel failures to Sentinel
    }
}
