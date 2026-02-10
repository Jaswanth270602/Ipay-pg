<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait LogsConditionally
{
    /**
     * Log a message conditionally based on TURNONLOGS env variable
     */
    protected function logIfEnabled($level, $message, $context = [])
    {
        if (config('app.turnonlogs', false)) {
            Log::{$level}($message, $context);
        }
    }

    /**
     * Log info message conditionally
     */
    protected function logInfo($message, $context = [])
    {
        $this->logIfEnabled('info', $message, $context);
    }

    /**
     * Log error message conditionally
     */
    protected function logError($message, $context = [])
    {
        $this->logIfEnabled('error', $message, $context);
    }

    /**
     * Log warning message conditionally
     */
    protected function logWarning($message, $context = [])
    {
        $this->logIfEnabled('warning', $message, $context);
    }

    /**
     * Log debug message conditionally
     */
    protected function logDebug($message, $context = [])
    {
        $this->logIfEnabled('debug', $message, $context);
    }
}

