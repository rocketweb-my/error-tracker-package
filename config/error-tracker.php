<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Key
    |--------------------------------------------------------------------------
    |
    | This is the API key used to authenticate with the error tracking dashboard.
    | You can find this key in your dashboard application.
    |
    */
    'api_key' => env('ERROR_TRACKER_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Application ID
    |--------------------------------------------------------------------------
    |
    | This is the unique identifier for this application in the error tracking system.
    | You can find this ID in your dashboard application.
    |
    */
    'app_id' => env('ERROR_TRACKER_APP_ID'),

    /*
    |--------------------------------------------------------------------------
    | Dashboard URL
    |--------------------------------------------------------------------------
    |
    | The URL of the error tracking dashboard where exceptions will be reported.
    |
    */
    'dashboard_url' => env('ERROR_TRACKER_DASHBOARD_URL', 'http://error-tracker.test'),

    /*
    |--------------------------------------------------------------------------
    | Enable Error Tracking
    |--------------------------------------------------------------------------
    |
    | This option can be used to enable/disable error tracking globally.
    |
    */
    'enabled' => env('ERROR_TRACKER_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Enabled Environments
    |--------------------------------------------------------------------------
    |
    | Define which environments should report errors to the dashboard.
    |
    */
    'environments' => [
        'production',
        'staging',
        'testing',
        'development',
    ],

    /*
    |--------------------------------------------------------------------------
    | Exclude Exceptions
    |--------------------------------------------------------------------------
    |
    | Specify exception classes that should not be reported.
    |
    */
    'exclude_exceptions' => [
        \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
        \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException::class,
        \Illuminate\Validation\ValidationException::class,
        // Add other exceptions you want to ignore
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Options
    |--------------------------------------------------------------------------
    |
    | Configure the HTTP client used to send error reports to the dashboard.
    |
    */
    'http_client' => [
        'timeout' => 5, // seconds
        'retry' => 3,   // number of retries on failure
    ],

    /*
    |--------------------------------------------------------------------------
    | Privacy Settings
    |--------------------------------------------------------------------------
    |
    | Configure which data should be sanitized before sending to the dashboard.
    |
    */
    'privacy' => [
        'sanitize_request_headers' => [
            'authorization',
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ],
        'sanitize_request_fields' => [
            'password',
            'password_confirmation',
            'token',
            'secret',
            'api_key',
        ],
    ],
];