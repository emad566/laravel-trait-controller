<?php

namespace EmadSoliman\LaravelTraitController\Helpers;

use Illuminate\Support\Facades\Log;

class CustomLogger
{
    /**
     * Log information message
     */
    public static function info(string $fileName, string $message, array $context = [], bool $isLog = false): void
    {
        if (!$isLog) {
            return;
        }

        // Set the log file path
        $logFilePath = storage_path('logs/' . $fileName);

        // Create a log channel dynamically
        Log::build([
            'driver' => 'single',
            'path' => $logFilePath,
            'level' => 'info',
        ])->info($message, $context);
    }

    /**
     * Log error message
     */
    public static function error(string $fileName, string $message, array $context = [], bool $isLog = true): void
    {
        if (!$isLog) {
            return;
        }

        // Set the log file path
        $logFilePath = storage_path('logs/' . $fileName);

        // Create a log channel dynamically
        Log::build([
            'driver' => 'single',
            'path' => $logFilePath,
            'level' => 'error',
        ])->error($message, $context);
    }

    /**
     * Log warning message
     */
    public static function warning(string $fileName, string $message, array $context = [], bool $isLog = true): void
    {
        if (!$isLog) {
            return;
        }

        // Set the log file path
        $logFilePath = storage_path('logs/' . $fileName);

        // Create a log channel dynamically
        Log::build([
            'driver' => 'single',
            'path' => $logFilePath,
            'level' => 'warning',
        ])->warning($message, $context);
    }

    /**
     * Log debug message
     */
    public static function debug(string $fileName, string $message, array $context = [], bool $isLog = false): void
    {
        if (!$isLog || !config('app.debug')) {
            return;
        }

        // Set the log file path
        $logFilePath = storage_path('logs/' . $fileName);

        // Create a log channel dynamically
        Log::build([
            'driver' => 'single',
            'path' => $logFilePath,
            'level' => 'debug',
        ])->debug($message, $context);
    }
}
