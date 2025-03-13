# Laravel Error Tracker

A simple and efficient error tracking package for Laravel applications. This package automatically captures exceptions in your Laravel applications and sends them to your centralized error tracking dashboard for monitoring and analysis.

## Features

- 🚀 **Simple to install and configure**
- 🔍 **Automatically captures all exceptions**
- 📊 **Groups similar errors to reduce noise**
- 🔒 **Secure API-based communication**
- 🧰 **Collects detailed context (request, user, system info)**
- 🔐 **Privacy-focused with data sanitization**

## Installation

You can install the package via composer:

```bash
composer require rocketweb/error-tracker

Configuration
After installation, publish the configuration file:

php artisan vendor:publish --provider="RocketWeb\ErrorTracker\ErrorTrackerServiceProvider"

Then, add the following environment variables to your .env file:

ERROR_TRACKER_API_KEY=your-api-key
ERROR_TRACKER_APP_ID=your-app-id
ERROR_TRACKER_DASHBOARD_URL=https://your-error-dashboard.com
ERROR_TRACKER_ENABLED=true

Usage
Once properly configured, the package will automatically capture and report exceptions from your Laravel application. No additional code is required!
Manual Exception Reporting
If you need to manually report exceptions, you can use the provided facade:

use RocketWeb\ErrorTracker\Facades\ErrorTracker;

try {
    // Your code here
} catch (\Exception $e) {
    ErrorTracker::reportException($e);
}


Configuration Options
The published configuration file (config/error-tracker.php) includes the following options:
Basic Configuration

api_key - Your API key for authentication
app_id - Your application's unique identifier
dashboard_url - URL of your error tracking dashboard
enabled - Enable/disable error tracking globally

Environment & Filtering

environments - List of environments where errors should be tracked
exclude_exceptions - Exception types to ignore

Privacy Settings

privacy.sanitize_request_headers - Headers to be sanitized
privacy.sanitize_request_fields - Request fields to be sanitized

HTTP Client Settings

http_client.timeout - Timeout for API requests
http_client.retry - Number of retry attempts

Dashboard Integration
This package is designed to work with the Laravel Error Tracking Dashboard. Follow the dashboard setup instructions to create your centralized error monitoring system.
Security
The package sanitizes sensitive data by default, including:

Authentication headers
Cookies
CSRF tokens
Password fields
API keys and tokens
