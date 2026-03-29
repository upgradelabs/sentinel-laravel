<?php

namespace UpgradeLabs\SentinelLaravel\Tests;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use UpgradeLabs\SentinelLaravel\SentinelReporter;

class SentinelReporterTest extends TestCase
{
    public function test_builds_payload_from_exception(): void
    {
        $reporter = app(SentinelReporter::class);

        $exception = new \RuntimeException('Something went wrong');
        $payload = $reporter->buildPayload($exception);

        $this->assertArrayNotHasKey('app_name', $payload);
        $this->assertEquals('RuntimeException', $payload['exception_class']);
        $this->assertEquals('Something went wrong', $payload['message']);
        $this->assertEquals('error', $payload['severity']);
        $this->assertNotEmpty($payload['file']);
        $this->assertNotEmpty($payload['stack_trace']);
        $this->assertArrayHasKey('laravel_version', $payload);
        $this->assertArrayHasKey('php_version', $payload);
        $this->assertArrayHasKey('environment', $payload);
    }

    public function test_fatal_severity_for_error_type(): void
    {
        $reporter = app(SentinelReporter::class);

        $exception = new \TypeError('Invalid type');
        $payload = $reporter->buildPayload($exception);

        $this->assertEquals('fatal', $payload['severity']);
    }

    public function test_ignores_configured_exceptions(): void
    {
        Http::fake();

        $reporter = app(SentinelReporter::class);

        $reporter->report(new ValidationException(
            validator([], [])
        ));

        Http::assertNothingSent();
    }

    public function test_reports_non_ignored_exceptions(): void
    {
        Http::fake([
            'sentinel.upgradelabs.pt/api/v1/report' => Http::response(['message' => 'OK'], 201),
        ]);

        $reporter = app(SentinelReporter::class);

        $reporter->report(new \RuntimeException('Test error'));

        Http::assertSent(function ($request) {
            return $request['exception_class'] === 'RuntimeException'
                && $request['message'] === 'Test error';
        });
    }

    public function test_includes_previous_exception_in_context(): void
    {
        $reporter = app(SentinelReporter::class);

        $previous = new \InvalidArgumentException('Original cause');
        $exception = new \RuntimeException('Wrapper', 0, $previous);

        $payload = $reporter->buildPayload($exception);

        $this->assertArrayHasKey('previous_exception', $payload['context']);
        $this->assertEquals('InvalidArgumentException', $payload['context']['previous_exception']['class']);
    }

    public function test_includes_exception_code_in_context(): void
    {
        $reporter = app(SentinelReporter::class);

        $exception = new \RuntimeException('Error', 42);
        $payload = $reporter->buildPayload($exception);

        $this->assertEquals(42, $payload['context']['code']);
    }
}
