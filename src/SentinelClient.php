<?php

namespace UpgradeLabs\SentinelLaravel;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class SentinelClient
{
    /** @var string */
    protected $baseUrl = 'https://sentinel.upgradelabs.pt';

    /** @var string|null */
    protected $token;

    /** @var int */
    protected $timeout;

    /**
     * @param  string|null  $token
     * @param  int  $timeout
     */
    public function __construct($token, $timeout = 5)
    {
        $this->token = $token;
        $this->timeout = $timeout;
    }

    /**
     * Send an error report payload to Sentinel.
     *
     * @param  array  $payload
     * @return Response|null
     */
    public function report(array $payload)
    {
        if (! $this->token) {
            return null;
        }

        $endpoint = $this->baseUrl . '/api/v1/report';

        try {
            return Http::withToken($this->token)
                ->timeout($this->timeout)
                ->acceptJson()
                ->post($endpoint, $payload);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Send a test error report to verify the connection.
     *
     * @return Response|null
     */
    public function testReport()
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

    /**
     * Record a deployment.
     *
     * @param  array  $data
     * @return Response|null
     */
    public function deploy(array $data = [])
    {
        if (! $this->token) {
            return null;
        }

        try {
            return Http::withToken($this->token)
                ->timeout($this->timeout)
                ->acceptJson()
                ->post($this->baseUrl . '/api/v1/deploy', $data);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Send a heartbeat ping.
     *
     * @return Response|null
     */
    public function heartbeat()
    {
        if (! $this->token) {
            return null;
        }

        try {
            return Http::withToken($this->token)
                ->timeout($this->timeout)
                ->acceptJson()
                ->get($this->baseUrl . '/api/v1/health');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @return string|null
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return $this->token !== null && $this->token !== '';
    }
}
