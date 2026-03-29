<?php

namespace UpgradeLabs\SentinelLaravel\Tests;

use Illuminate\Support\Facades\Http;
use UpgradeLabs\SentinelLaravel\SentinelClient;

class SentinelClientTest extends TestCase
{
    public function test_client_is_configured(): void
    {
        $client = app(SentinelClient::class);

        $this->assertTrue($client->isConfigured());
        $this->assertEquals('test-token', $client->getToken());
    }

    public function test_client_sends_report(): void
    {
        Http::fake([
            'sentinel.upgradelabs.pt/api/v1/report' => Http::response(['message' => 'OK'], 201),
        ]);

        $client = app(SentinelClient::class);

        $response = $client->report([
            'exception_class' => 'RuntimeException',
            'message' => 'Test error',
        ]);

        $this->assertNotNull($response);
        $this->assertEquals(201, $response->status());

        Http::assertSent(function ($request) {
            return $request->url() === 'https://sentinel.upgradelabs.pt/api/v1/report'
                && $request->hasHeader('Authorization', 'Bearer test-token')
                && $request['exception_class'] === 'RuntimeException';
        });
    }

    public function test_client_returns_null_when_not_configured(): void
    {
        $client = new SentinelClient(null);

        $this->assertFalse($client->isConfigured());
        $this->assertNull($client->report(['test' => true]));
    }

    public function test_client_fails_silently_on_network_error(): void
    {
        Http::fake([
            'sentinel.upgradelabs.pt/api/v1/report' => Http::throw(fn () => throw new \Exception('Connection refused')),
        ]);

        $client = app(SentinelClient::class);

        $response = $client->report(['exception_class' => 'RuntimeException', 'message' => 'Error']);

        $this->assertNull($response);
    }
}
