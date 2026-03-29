<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sentinel URL
    |--------------------------------------------------------------------------
    |
    | The base URL of your Sentinel dashboard instance.
    | Example: https://sentinel.test or https://sentinel.yourdomain.com
    |
    */

    'url' => env('SENTINEL_URL'),

    /*
    |--------------------------------------------------------------------------
    | API Token
    |--------------------------------------------------------------------------
    |
    | The API token for your project in Sentinel. You can find this in the
    | project settings on your Sentinel dashboard.
    |
    */

    'token' => env('SENTINEL_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    |
    | Enable or disable Sentinel error reporting. When disabled, no errors
    | will be sent to Sentinel.
    |
    */

    'enabled' => env('SENTINEL_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Environments
    |--------------------------------------------------------------------------
    |
    | Only report errors from these environments. Set to null or empty array
    | to report from all environments.
    |
    */

    'environments' => env('SENTINEL_ENVIRONMENTS') ? explode(',', env('SENTINEL_ENVIRONMENTS')) : null,

    /*
    |--------------------------------------------------------------------------
    | Ignored Exceptions
    |--------------------------------------------------------------------------
    |
    | A list of exception classes that should not be reported to Sentinel.
    |
    */

    'ignored_exceptions' => [
        Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
        Illuminate\Validation\ValidationException::class,
        Illuminate\Auth\AuthenticationException::class,
        Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Collect User Data
    |--------------------------------------------------------------------------
    |
    | When enabled, Sentinel will collect authenticated user information
    | (id, email) and attach it to the error report.
    |
    */

    'collect_user' => true,

    /*
    |--------------------------------------------------------------------------
    | Collect Request Data
    |--------------------------------------------------------------------------
    |
    | When enabled, Sentinel will collect request data (URL, method, headers,
    | input) and attach it to the error report.
    |
    */

    'collect_request' => true,

    /*
    |--------------------------------------------------------------------------
    | Request Data Filtering
    |--------------------------------------------------------------------------
    |
    | Fields that should be redacted from request data before sending.
    |
    */

    'filtered_fields' => [
        'password',
        'password_confirmation',
        'token',
        'secret',
        'credit_card',
        'card_number',
        'cvv',
        'ssn',
    ],

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    |
    | Timeout in seconds for the HTTP request to Sentinel.
    |
    */

    'timeout' => 5,

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    |
    | Send error reports via a queued job instead of synchronously. This
    | prevents Sentinel from slowing down your application if the Sentinel
    | server is slow or unreachable.
    |
    | Set to null to send synchronously, or specify a queue name.
    |
    */

    'queue' => env('SENTINEL_QUEUE'),

];
