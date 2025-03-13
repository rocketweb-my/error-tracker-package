<?php

namespace RocketWeb\ErrorTracker;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class ErrorTrackerExceptionHandler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        // Call parent register method if it exists (Laravel 8+)
        if (method_exists(parent::class, 'register')) {
            parent::register();
        }
    }

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $e
     * @return void
     *
     * @throws \Throwable
     */
    public function report(Throwable $e)
    {
        // Report exception to error tracker if it should be reported
        if ($this->shouldReport($e)) {
            try {
                app(ErrorTracker::class)->reportException($e);
            } catch (\Exception $trackerException) {
                // If error tracker fails, log it but don't disrupt normal exception handling
                \Illuminate\Support\Facades\Log::error(
                    'Error tracker failed to report exception: ' . $trackerException->getMessage()
                );
            }
        }

        // Continue with normal exception reporting
        parent::report($e);
    }

    /**
     * Determine if the exception should be reported.
     *
     * @param  \Throwable  $e
     * @return bool
     */
    public function shouldReport(Throwable $e)
    {
        // Get excluded exceptions from config
        $excludedExceptions = config('error-tracker.exclude_exceptions', []);

        // Check if exception is in the excluded list
        foreach ($excludedExceptions as $excludedException) {
            if ($e instanceof $excludedException) {
                return false;
            }
        }

        // Also check parent's shouldReport method
        return parent::shouldReport($e);
    }
}