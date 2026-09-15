<?php

namespace App\Services\IdentityProviderService\Exceptions;

use RuntimeException;
use Throwable;

class IdentityProviderException extends RuntimeException
{
    /**
     * @param string $errorCode
     * @param string $message
     * @param ?Throwable $previous
     * @param array $context
     */
    public function __construct(
        protected string $errorCode,
        string $message,
        ?Throwable $previous = null,
        protected array $context = [],
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * @return string
     */
    public function errorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * @return array
     */
    public function context(): array
    {
        return $this->context;
    }
}
