<?php

namespace App\Services\IdentityProviderService\Exceptions;

use App\Services\IdentityProviderService\Services\IdentityProviderLogService;
use Throwable;

class IdentityProviderHttpFailure
{
    /**
     * @param Throwable $exception
     * @param string $operation
     * @param array $context
     * @param string $fallbackCode
     * @param string $fallbackMessage
     * @return IdentityProviderHttpException
     */
    public static function toException(
        Throwable $exception,
        string $operation,
        array $context,
        string $fallbackCode,
        string $fallbackMessage,
    ): IdentityProviderHttpException {
        $expected = $exception instanceof IdentityProviderException;
        $status = $expected ? 409 : 503;

        $diagnosticId = resolve(IdentityProviderLogService::class)->exceptionFailure(
            $operation,
            $exception,
            [...$context, 'http_status' => $status],
            $fallbackCode,
        );

        return new IdentityProviderHttpException(
            $status,
            $expected ? $exception->getMessage() : $fallbackMessage,
            $diagnosticId,
        );
    }
}
