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
- **Custom context** (order_id, payment_method, etc.)
- **Breadcrumbs** (DB queries, cache, jobs — sequence of events before the error)
- **Performance** (request duration, memory usage — when middleware is active)

### Data Privacy

Sensitive fields are automatically redacted from request data:

- `password`, `password_confirmation`
- `token`, `secret`
- `credit_card`, `card_number`, `cvv`, `ssn`
- Authorization, Cookie, and CSRF headers

## Deploy Tracking

Notify Sentinel when you deploy so you can correlate errors with releases.

### Artisan command (recommended)

```bash
# Auto-detect version, commit, and branch from git
php artisan sentinel:deploy --auto

# With explicit values
php artisan sentinel:deploy --tag=1.2.0 --deployer="GitHub Actions"

# Full options
php artisan sentinel:deploy \
  --tag=1.2.0 \
  --commit=abc123def \
  --branch=main \
  --environment=production \
  --deployer="CI/CD" \
  --description="Fix payment bug"
```

All flags are optional. Use `--auto` to detect tag (from git tag), commit hash, and branch automatically.

### In CI/CD pipelines

**GitHub Actions:**
```yaml
- name: Notify Sentinel of deploy
  run: php artisan sentinel:deploy --auto --deployer="GitHub Actions"
```

**Laravel Forge / Envoyer (deploy script):**
```bash
php artisan sentinel:deploy --auto --deployer="Forge"
```

**Or with curl (no package needed):**
```bash
curl -X POST https://sentinel.upgradelabs.pt/api/v1/deploy \
  -H "Authorization: Bearer $SENTINEL_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "version": "1.2.0",
    "commit_hash": "'$(git rev-parse HEAD)'",
    "branch": "'$(git branch --show-current)'",
    "environment": "production",
    "deployer": "CI/CD"
  }'
```

### Programmatic usage

```php
app(\UpgradeLabs\SentinelLaravel\SentinelClient::class)->deploy([
    'version' => '1.2.0',
    'commit_hash' => 'abc123',
    'branch' => 'main',
    'environment' => 'production',
    'deployer' => 'CI/CD',
]);
```

## Context Enrichment

Add custom context that gets attached to any error that occurs during the request:

```php
use UpgradeLabs\SentinelLaravel\SentinelContext;

// Add context — available in the error report's "context" section
SentinelContext::set([
    'order_id' => 123,
    'payment_method' => 'stripe',
    'subscription_plan' => 'pro',
]);

// Context is automatically cleared after each error report
```

## Breadcrumbs

Breadcrumbs automatically track the sequence of events leading up to an error. Enabled by default.

**Auto-captured events:**
- Database queries (SQL, time, connection)
- Cache hits and misses
- Queue job processing

**Add manual breadcrumbs:**
```php
use UpgradeLabs\SentinelLaravel\SentinelContext;

SentinelContext::breadcrumb('payment', 'Charging customer', ['amount' => 99.99]);
SentinelContext::breadcrumb('api', 'Called Stripe API', ['endpoint' => '/charges']);
```

**Configure in `config/sentinel.php`:**
```php
'breadcrumbs' => [
    'enabled' => true,
    'queries' => true,   // DB queries
    'cache' => false,     // Cache events
    'jobs' => true,       // Queue jobs
],
```

## Performance Tracking

Track request duration and memory usage by adding the middleware:

```php
// In app/Http/Kernel.php or bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \UpgradeLabs\SentinelLaravel\Middleware\TrackSentinelPerformance::class,
    ]);
})
```

Performance data (duration_ms, memory_peak_mb) is automatically included in error reports.

## Log Channel

Send Laravel log entries to Sentinel by adding a custom log channel:

```php
// config/logging.php
'channels' => [
    'sentinel' => [
        'driver' => 'custom',
        'via' => \UpgradeLabs\SentinelLaravel\SentinelLogChannel::class,
        'level' => 'error', // Only send error and above
    ],
],
```

Then use it alongside your default channel:

```php
// .env
LOG_CHANNEL=stack
LOG_STACK=daily,sentinel

// Or log to Sentinel explicitly
Log::channel('sentinel')->error('Payment failed', ['order_id' => 123]);
```

## Read API

Pull data from Sentinel for external tools (Grafana, custom dashboards):

```bash
# Project stats
curl -H "Authorization: Bearer TOKEN" https://sentinel.upgradelabs.pt/api/v1/stats

# Errors (paginated, filterable by status/severity)
curl -H "Authorization: Bearer TOKEN" https://sentinel.upgradelabs.pt/api/v1/errors?status=unresolved

# Deploys
curl -H "Authorization: Bearer TOKEN" https://sentinel.upgradelabs.pt/api/v1/deploys

# Downtime history
curl -H "Authorization: Bearer TOKEN" https://sentinel.upgradelabs.pt/api/v1/downtime
```

## Testing

### Verify configuration

```bash
php artisan tinker

>>> app(\UpgradeLabs\SentinelLaravel\SentinelClient::class)->isConfigured()
// Should return: true
```

### Test error reporting

```bash
php artisan tinker

>>> $response = app(\UpgradeLabs\SentinelLaravel\SentinelClient::class)->testReport()
>>> $response->status()   // Should return: 201
>>> $response->json()     // Should show: {"message": "Error reported successfully.", ...}

# Or test with a real exception
>>> app(\UpgradeLabs\SentinelLaravel\SentinelReporter::class)->report(new \RuntimeException('Test from tinker'))
```

### Test heartbeat

Send a manual heartbeat to verify your app shows as "Online" on the Sentinel status page:

```bash
php artisan tinker

>>> $response = app(\UpgradeLabs\SentinelLaravel\SentinelClient::class)->heartbeat()
>>> $response->status()   // Should return: 200
>>> $response->json()     // Should show: {"message": "pong", "project": "...", ...}
```

Or test with curl using your project's API token:

```bash
curl -H "Authorization: Bearer YOUR_PROJECT_TOKEN" \
  https://sentinel.upgradelabs.pt/api/v1/health
```

Once a heartbeat is received, your project shows as **Online** on the status page. If no heartbeat is received for 10+ minutes, it switches to **Offline** and a critical alert email is sent.

With the scheduler running (`* * * * * php artisan schedule:run`), heartbeats are sent automatically every 5 minutes — no manual work needed.

## License

MIT
