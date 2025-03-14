<?php

namespace RocketWeb\ErrorTracker;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Throwable;

class ErrorTracker
{
    /**
     * The API key for authentication.
     *
     * @var string
     */
    protected $apiKey;

    /**
     * The application ID.
     *
     * @var string
     */
    protected $appId;

    /**
     * The dashboard URL.
     *
     * @var string
     */
    protected $dashboardUrl;

    /**
     * The HTTP client instance.
     *
     * @var \GuzzleHttp\Client
     */
    protected $httpClient;

    /**
     * Create a new ErrorTracker instance.
     *
     * @param string $apiKey
     * @param string $appId
     * @param string $dashboardUrl
     * @return void
     */
    public function __construct($apiKey, $appId, $dashboardUrl)
    {
        $this->apiKey = $apiKey;
        $this->appId = $appId;
        $this->dashboardUrl = $dashboardUrl;
        $this->httpClient = new Client([
            'timeout' => config('error-tracker.http_client.timeout', 5),
            'verify' => false, // Ignore SSL certificate issues for local development
        ]);
    }

    /**
     * Report an exception to the error tracking dashboard.
     *
     * @param  \Throwable  $exception
     * @return bool
     */
    public function reportException(Throwable $exception)
    {
        // Check if error tracking is enabled
        if (!config('error-tracker.enabled', true)) {
            Log::info('ErrorTracker is disabled');
            return false;
        }

        // Check if the current environment should report errors
        $environment = app()->environment();
        if (!in_array($environment, config('error-tracker.environments', ['production']))) {
            Log::info('ErrorTracker: Current environment not in tracking list', ['env' => $environment]);
            return false;
        }

        // Check if this exception should be excluded
        $excludedExceptions = config('error-tracker.exclude_exceptions', []);
        foreach ($excludedExceptions as $excludedException) {
            if ($exception instanceof $excludedException) {
                Log::info('ErrorTracker: Exception type excluded', ['type' => get_class($exception)]);
                return false;
            }
        }

        try {
            Log::info('ErrorTracker: Reporting exception', [
                'type' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine()
            ]);
            
            // Format the exception data
            $data = $this->formatException($exception);
            
            // Send the data to the dashboard
            return $this->sendToApi($data);
        } catch (\Exception $e) {
            // Log any errors that occur during reporting
            Log::error('Failed to report exception to error tracker: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return false;
        }
    }

    /**
     * Format the exception data.
     *
     * @param  \Throwable  $exception
     * @return array
     */
    protected function formatException(Throwable $exception)
    {
        $request = request();
        
        // Get authenticated user if available
        $user = $request->user();
        $userData = null;
        
        if ($user) {
            $userData = [
                'id' => $user->id,
                'email' => $user->email ?? null,
                'name' => $user->name ?? null,
            ];
        }

        // Format exception details
        return [
            'application_id' => $this->appId,
            'exception_type' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'stack_trace' => $this->formatStackTrace($exception),
            'request_data' => [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'headers' => $this->sanitizeHeaders($request->headers->all()),
                'query' => $request->query(),
                'body' => $this->sanitizeBody($request->except(config('error-tracker.privacy.sanitize_request_fields', []))),
            ],
            'user_data' => $userData,
            'system_data' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'server' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : null,
            ],
            'environment' => app()->environment(),
            'occurred_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Format the stack trace.
     *
     * @param  \Throwable  $exception
     * @return array
     */
    protected function formatStackTrace(Throwable $exception)
    {
        $trace = [];
        foreach ($exception->getTrace() as $frame) {
            $trace[] = [
                'file' => $frame['file'] ?? '[internal function]',
                'line' => $frame['line'] ?? null,
                'function' => $frame['function'] ?? null,
                'class' => $frame['class'] ?? null,
                'type' => $frame['type'] ?? null,
                'args' => isset($frame['args']) ? $this->sanitizeArgs($frame['args']) : null,
            ];
        }
        
        return $trace;
    }

    /**
     * Sanitize sensitive data from headers.
     *
     * @param  array  $headers
     * @return array
     */
    protected function sanitizeHeaders(array $headers)
    {
        $sensitiveHeaders = config('error-tracker.privacy.sanitize_request_headers', [
            'authorization',
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);

        foreach ($sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = '[REDACTED]';
            }
        }

        return $headers;
    }

    /**
     * Sanitize request body.
     *
     * @param  array  $body
     * @return array
     */
    protected function sanitizeBody(array $body)
    {
        $sensitiveFields = config('error-tracker.privacy.sanitize_request_fields', [
            'password',
            'password_confirmation',
            'token',
            'secret',
            'api_key',
        ]);

        foreach ($body as $key => $value) {
            if (is_array($value)) {
                $body[$key] = $this->sanitizeBody($value);
            } else {
                foreach ($sensitiveFields as $field) {
                    if (stripos($key, $field) !== false) {
                        $body[$key] = '[REDACTED]';
                        break;
                    }
                }
            }
        }

        return $body;
    }

    /**
     * Sanitize stack trace args.
     *
     * @param  array  $args
     * @return array
     */
    protected function sanitizeArgs(array $args)
    {
        foreach ($args as $key => $arg) {
            if (is_object($arg)) {
                $args[$key] = get_class($arg);
            } elseif (is_array($arg)) {
                $args[$key] = '[array]';
            } elseif (is_resource($arg)) {
                $args[$key] = '[resource]';
            }
        }

        return $args;
    }

    /**
     * Send exception data to the API.
     *
     * @param  array  $data
     * @return bool
     */
    protected function sendToApi(array $data)
    {
        // Configure the HTTP client to ignore SSL certificate issues
        $this->httpClient = new Client([
            'timeout' => config('error-tracker.http_client.timeout', 5),
            'verify' => false, // Ignore SSL certificate issues
        ]);

        $retries = config('error-tracker.http_client.retry', 3);
        $attempt = 0;
        $success = false;

        // Make sure we're using HTTPS
        $url = rtrim($this->dashboardUrl, '/');
        if (!str_starts_with($url, 'https://')) {
            $url = str_replace('http://', 'https://', $url);
        }
        $url .= '/api/errors';

        while ($attempt < $retries && !$success) {
            try {
                Log::info('ErrorTracker sending request', [
                    'url' => $url,
                    'attempt' => $attempt + 1,
                    'app_id' => $this->appId,
                    'api_key_exists' => !empty($this->apiKey)
                ]);

                $response = $this->httpClient->request(
                    'POST',
                    $url,
                    [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $this->apiKey,
                            'Content-Type' => 'application/json',
                            'Accept' => 'application/json',
                        ],
                        'json' => $data,
                        'http_errors' => false,
                    ]
                );

                $statusCode = $response->getStatusCode();
                $success = $statusCode >= 200 && $statusCode < 300;
                
                Log::info('ErrorTracker received response', [
                    'status_code' => $statusCode,
                    'success' => $success,
                    'response' => (string)$response->getBody()
                ]);
                
                if ($success) {
                    return true;
                }
            } catch (\Exception $e) {
                Log::error('Error sending exception to API (attempt ' . ($attempt + 1) . '): ' . $e->getMessage(), [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
            }

            $attempt++;
            
            if ($attempt < $retries) {
                usleep(200000); // 200ms
            }
        }

        return $success;
    }
}