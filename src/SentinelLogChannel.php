<?php

namespace UpgradeLabs\SentinelLaravel;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Psr\Log\LoggerInterface;

class SentinelLogChannel
{
    /**
     * Create a custom Monolog instance.
     *
     * @param  array  $config
     * @return LoggerInterface
     */
    public function __invoke(array $config)
    {
        $logger = new \Monolog\Logger('sentinel');
        $logger->pushHandler(new SentinelLogHandler(
            isset($config['level']) ? Level::fromName($config['level']) : Level::Error
        ));

        return $logger;
    }
}

class SentinelLogHandler extends AbstractProcessingHandler
{
    protected function write(LogRecord $record): void
    {
        $client = app(SentinelClient::class);

        if (! $client->isConfigured()) {
            return;
        }

        $severityMap = [
            'emergency' => 'fatal',
            'alert' => 'fatal',
            'critical' => 'fatal',
            'error' => 'error',
            'warning' => 'warning',
            'notice' => 'notice',
            'info' => 'info',
            'debug' => 'info',
        ];

        $level = strtolower($record->level->name);

        $payload = [
            'exception_class' => 'Log\\' . ucfirst($level),
            'message' => $record->message,
            'file' => $record->context['file'] ?? 'log',
            'line' => $record->context['line'] ?? 0,
            'severity' => $severityMap[$level] ?? 'error',
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'environment' => app()->environment(),
            'context' => $record->context,
        ];

        try {
            $client->report($payload);
        } catch (\Throwable $e) {
            // Silent fail
        }
    }
}
