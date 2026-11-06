<?php

namespace App\Services\IdentityProviderService\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class IdentityProviderHttpException extends HttpException
{
    /**
     * @param int $statusCode
     * @param string $message
     * @param string $diagnosticId
     */
    public function __construct(int $statusCode, string $message, string $diagnosticId)
    {
        parent::__construct($statusCode, __('exceptions.identity_providers.with_reference', [
            'message' => rtrim($message, '.'),
            'reference' => $diagnosticId,
        ]));
    }
}
