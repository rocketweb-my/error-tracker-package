<?php

namespace RocketWeb\ErrorTracker;

use Illuminate\Support\ServiceProvider;

class ErrorTrackerServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__.'/../config/error-tracker.php', 'error-tracker'
        );

        // Register the ErrorTracker singleton
        $this->app->singleton(ErrorTracker::class, function ($app) {
            return new ErrorTracker(
                config('error-tracker.api_key'),
                config('error-tracker.app_id'),
                config('error-tracker.dashboard_url')
            );
        });

        // Register facade
        $this->app->alias(ErrorTracker::class, 'error-tracker');
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish config
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/error-tracker.php' => config_path('error-tracker.php'),
            ], 'error-tracker-config');
        }

        // Register exception handler if tracking is enabled
        if (config('error-tracker.enabled', true)) {
            $this->app->singleton(
                \Illuminate\Contracts\Debug\ExceptionHandler::class,
                ErrorTrackerExceptionHandler::class
            );
        }
    }
}