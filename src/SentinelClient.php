<?php

namespace UpgradeLabs\SentinelLaravel;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class SentinelClient
{
    protected string $baseUrl = 'https://sentinel.upgradelabs.pt';

    public function __construct(
        protected ?string $token,
        protected int $timeout = 5,
    ) {}

    /**
     * Send an error report payload to Sentinel.
     *
     * @param  array<string, mixed>  $payload
     */
    public function report(array $payload): ?Response
    {
        if (! $this->token) {
            return null;
        }

        $endpoint = $this->baseUrl.'/api/v1/report';

        try {
            return Http::withToken($this->token)
                ->timeout($this->timeout)
                ->acceptJson()
                ->post($endpoint, $payload);
        } catch (\Throwable) {
            // Silently fail — we don't want Sentinel reporting to break the app
            return null;
        }
    }

    /**
     * Send a test error report to verify the connection.
     */
    public function testReport(): ?Response
    {
        return $this->report([
            'exception_class' => 'UpgradeLabs\\SentinelLaravel\\TestException',
            'message' => 'This is a test error from Sentinel Laravel package.',
            'file' => 'tinker',
            'line' => 1,
            'severity' => 'info',
            'stack_trace' => '#0 tinker: test report',
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'environment' => app()->environment(),
        ]);
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function isConfigured(): bool
    {
        return $this->token !== null && $this->token !== '';
    }
}
