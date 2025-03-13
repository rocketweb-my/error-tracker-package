<?php

namespace RocketWeb\ErrorTracker\Facades;

use Illuminate\Support\Facades\Facade;

class ErrorTracker extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'error-tracker';
    }
}