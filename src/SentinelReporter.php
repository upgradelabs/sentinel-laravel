<?php

namespace UpgradeLabs\SentinelLaravel;

use UpgradeLabs\SentinelLaravel\Jobs\SendErrorReport;

class SentinelReporter
{
    public function __construct(
        protected SentinelClient $client,
    ) {}

    public function report(\Throwable $exception): void
    {
        if ($this->shouldIgnore($exception)) {
            return;
        }

        $payload = $this->buildPayload($exception);

        $queue = config('sentinel.queue');

        if ($queue !== null) {
            dispatch(new SendErrorReport($payload))->onQueue($queue);
        } else {
            $this->client->report($payload);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function buildPayload(\Throwable $exception): array
    {
        $payload = [
            'app_name' => config('app.name', 'Laravel'),
            'exception_class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'stack_trace' => $exception->getTraceAsString(),
            'severity' => $this->determineSeverity($exception),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'environment' => app()->environment(),
            'app_url' => config('app.url'),
        ];

        if (config('sentinel.collect_request', true) && ! app()->runningInConsole()) {
            $payload = array_merge($payload, $this->collectRequestData());
        }

        if (config('sentinel.collect_user', true)) {
            $payload['user_data'] = $this->collectUserData();
        }

        $payload['context'] = $this->collectContext($exception);

        return $payload;
    }

    protected function shouldIgnore(\Throwable $exception): bool
    {
        $ignored = config('sentinel.ignored_exceptions', []);

        foreach ($ignored as $class) {
            if ($exception instanceof $class) {
                return true;
            }
        }

        return false;
    }

    protected function determineSeverity(\Throwable $exception): string
    {
        if ($exception instanceof \Error) {
            return 'fatal';
        }

        if ($exception instanceof \ErrorException) {
            return match ($exception->getSeverity()) {
                E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR => 'fatal',
                E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING => 'warning',
                E_NOTICE, E_USER_NOTICE, E_STRICT, E_DEPRECATED, E_USER_DEPRECATED => 'notice',
                default => 'error',
            };
        }

        return 'error';
    }

    /**
     * @return array<string, mixed>
     */
    protected function collectRequestData(): array
    {
        try {
            $request = request();

            $filteredFields = config('sentinel.filtered_fields', []);

            $input = $request->except($filteredFields);

            // Redact any nested filtered fields
            foreach ($filteredFields as $field) {
                array_walk_recursive($input, function (&$value, $key) use ($field) {
                    if (strcasecmp($key, $field) === 0) {
                        $value = '********';
                    }
                });
            }

            return [
                'url' => $request->fullUrl(),
                'request_method' => $request->method(),
                'request_data' => [
                    'query' => $request->query(),
                    'body' => $input,
                    'headers' => $this->filterHeaders($request->headers->all()),
                ],
            ];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @param  array<string, mixed>  $headers
     * @return array<string, mixed>
     */
    protected function filterHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'cookie', 'x-csrf-token', 'x-xsrf-token'];

        foreach ($sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = ['********'];
            }
        }

        return $headers;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function collectUserData(): ?array
    {
        try {
            $user = auth()->user();

            if (! $user) {
                return null;
            }

            return [
                'id' => $user->getAuthIdentifier(),
                'email' => $user->email ?? null,
                'name' => $user->name ?? null,
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function collectContext(\Throwable $exception): array
    {
        $context = [];

        // Include previous exception info
        if ($exception->getPrevious()) {
            $prev = $exception->getPrevious();
            $context['previous_exception'] = [
                'class' => get_class($prev),
                'message' => $prev->getMessage(),
                'file' => $prev->getFile(),
                'line' => $prev->getLine(),
            ];
        }

        // Include exception code if set
        if ($exception->getCode()) {
            $context['code'] = $exception->getCode();
        }

        return $context;
    }
}
