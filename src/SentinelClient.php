<?php

namespace UpgradeLabs\SentinelLaravel;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class SentinelClient
{
    public function __construct(
        protected ?string $url,
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
        if (! $this->url || ! $this->token) {
            return null;
        }

        $endpoint = rtrim($this->url, '/').'/api/v1/report';

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

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function isConfigured(): bool
    {
        return $this->url !== null && $this->token !== null;
    }
}
