<?php

namespace App\Services\DigIdService;

use Illuminate\Support\Facades\Log;
use Throwable;

class DigIdServiceLogger
{
    /**
     * @param string $message
     * @param Throwable|null $exception
     * @param array $context
     * @return void
     */
    public static function logError(string $message, ?Throwable $exception = null, array $context = []): void
    {
        Log::channel('digid')->error($message, [
            ...$context,
            ...($exception ? ['exception' => $exception] : []),
        ]);
    }
}
