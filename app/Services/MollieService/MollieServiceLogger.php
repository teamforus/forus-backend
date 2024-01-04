<?php


namespace App\Services\MollieService;

use Illuminate\Support\Facades\Log;
use Throwable;

class MollieServiceLogger
{
    /**
     * @param string $message
     * @param Throwable|null $e
     * @return void
     */
    public static function logError(string $message, ?Throwable $e): void
    {
        Log::channel('mollie')->error(implode("\n", array_filter([
            $message,
            $e?->getMessage(),
            $e?->getTraceAsString(),
        ])));
    }
}
