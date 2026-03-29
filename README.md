# Sentinel Laravel

Error reporting client for [Sentinel](https://sentinel.upgradelabs.pt). Captures exceptions from your Laravel application and sends them to your Sentinel dashboard.

**Compatible with Laravel 8, 9, 10, 11, 12, and 13.**

## Installation

```bash
composer require upgradelabs/sentinel-laravel
```

## Configuration

Add your Sentinel API token to `.env`:

```env
SENTINEL_TOKEN=your-project-api-token
```

That's it. Sentinel will automatically capture and report unhandled exceptions.

The token identifies your project — you get it when creating a project on the Sentinel dashboard or via `php artisan sentinel:create-project` on the Sentinel server.

### Publish config (optional)

```bash
php artisan vendor:publish --tag=sentinel-config
```

## Optional Configuration

### Report only from specific environments

```env
SENTINEL_ENVIRONMENTS=production,staging
```

### Send reports via queue (recommended for production)

```env
SENTINEL_QUEUE=default
```

### Disable reporting

```env
SENTINEL_ENABLED=false
```

### Heartbeat (automatic)

Sentinel pings the dashboard every 5 minutes to report your app is alive. This powers the status page. It's enabled by default — requires the Laravel scheduler to be running:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

To disable:

```env
SENTINEL_HEARTBEAT=false
```

### Ignored exceptions

Edit `config/sentinel.php` to customize which exceptions are ignored:

```php
'ignored_exceptions' => [
    Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
    Illuminate\Validation\ValidationException::class,
],
```

## Manual Reporting

You can manually report exceptions:

```php
// Report an exception manually
try {
    // risky operation
} catch (\Throwable $e) {
    app(\UpgradeLabs\SentinelLaravel\SentinelReporter::class)->report($e);
}
```

## Laravel 8/9 (Manual Handler Setup)

For older Laravel versions where automatic `reportable()` registration may not work, add the trait to your exception handler:

```php
// app/Exceptions/Handler.php
use UpgradeLabs\SentinelLaravel\ReportsToSentinel;

class Handler extends ExceptionHandler
{
    use ReportsToSentinel;

    public function register(): void
    {
        $this->reportable(function (\Throwable $e) {
            $this->reportToSentinel($e);
        });
    }
}
```

## What Gets Reported

Each error report includes:

- **Exception class, message, file, line**
- **Full stack trace**
- **Severity** (auto-detected: fatal, error, warning, notice)
- **Laravel & PHP version**
- **Environment** (production, staging, local, etc.)
- **Request data** (URL, method, headers, input — sensitive fields redacted)
- **Authenticated user** (id, email, name)
- **Previous exception** chain

### Data Privacy

Sensitive fields are automatically redacted from request data:

- `password`, `password_confirmation`
- `token`, `secret`
- `credit_card`, `card_number`, `cvv`, `ssn`
- Authorization, Cookie, and CSRF headers

## Testing

Verify your setup:

```bash
php artisan tinker

# Check configuration
>>> app(\UpgradeLabs\SentinelLaravel\SentinelClient::class)->isConfigured()
// Should return: true

# Send a test error report
>>> $response = app(\UpgradeLabs\SentinelLaravel\SentinelClient::class)->testReport()
>>> $response->status()   // Should return: 201
>>> $response->json()     // Should show: {"message": "Error reported successfully.", ...}

# Or test with a real exception
>>> app(\UpgradeLabs\SentinelLaravel\SentinelReporter::class)->report(new \RuntimeException('Test from tinker'))
```

## License

MIT
