<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

class MetrikaLog
{
    public static function enabled(): bool
    {
        return (bool) config('metrika.reporting_log', false);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function info(string $message, array $context = []): void
    {
        if (self::enabled()) {
            Log::channel('metrika')->info($message, $context);
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function warning(string $message, array $context = []): void
    {
        Log::channel('metrika')->warning($message, $context);
    }
}
