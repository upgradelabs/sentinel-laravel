<?php

namespace UpgradeLabs\SentinelLaravel;

use UpgradeLabs\SentinelLaravel\Jobs\SendErrorReport;

class SentinelReporter
{
    /** @var SentinelClient */
    protected $client;

    public function __construct(SentinelClient $client)
    {
        $this->client = $client;
    }

    /**
     * @return void
     */
    public function report(\Throwable $exception)
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
     * @return array
     */
    public function buildPayload(\Throwable $exception)
    {
        $payload = [
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

        // Merge custom context + breadcrumbs + performance
        $context = $this->collectContext($exception);
        $customContext = SentinelContext::get();
        if (! empty($customContext)) {
            $context = array_merge($context, $customContext);
        }
        $payload['context'] = $context;

        $breadcrumbs = SentinelContext::getBreadcrumbs();
        if (! empty($breadcrumbs)) {
            $payload['context']['breadcrumbs'] = $breadcrumbs;
        }

        // Clear after collecting
        SentinelContext::flush();

        return $payload;
    }

    /**
     * @return bool
     */
    protected function shouldIgnore(\Throwable $exception)
    {
        $ignored = config('sentinel.ignored_exceptions', []);

        foreach ($ignored as $class) {
            if ($exception instanceof $class) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string
     */
    protected function determineSeverity(\Throwable $exception)
    {
        if ($exception instanceof \Error) {
            return 'fatal';
        }

        if ($exception instanceof \ErrorException) {
            $severity = $exception->getSeverity();

            switch ($severity) {
                case E_ERROR:
                case E_CORE_ERROR:
                case E_COMPILE_ERROR:
                case E_USER_ERROR:
                    return 'fatal';
                case E_WARNING:
                case E_CORE_WARNING:
                case E_COMPILE_WARNING:
                case E_USER_WARNING:
                    return 'warning';
                case E_NOTICE:
                case E_USER_NOTICE:
                case E_STRICT:
                case E_DEPRECATED:
                case E_USER_DEPRECATED:
                    return 'notice';
                default:
                    return 'error';
            }
        }

        return 'error';
    }

    /**
     * @return array
     */
    protected function collectRequestData()
    {
        try {
            $request = request();

            $filteredFields = config('sentinel.filtered_fields', []);

            $input = $request->except($filteredFields);

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
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @return array
     */
    protected function filterHeaders(array $headers)
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
     * @return array|null
     */
    protected function collectUserData()
    {
        try {
            $user = auth()->user();

            if (! $user) {
                return null;
            }

            return [
                'id' => $user->getAuthIdentifier(),
                'email' => isset($user->email) ? $user->email : null,
                'name' => isset($user->name) ? $user->name : null,
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @return array
     */
    protected function collectContext(\Throwable $exception)
    {
        $context = [];

        if ($exception->getPrevious()) {
            $prev = $exception->getPrevious();
            $context['previous_exception'] = [
                'class' => get_class($prev),
                'message' => $prev->getMessage(),
                'file' => $prev->getFile(),
                'line' => $prev->getLine(),
            ];
        }

        if ($exception->getCode()) {
            $context['code'] = $exception->getCode();
        }

        return $context;
    }
}
