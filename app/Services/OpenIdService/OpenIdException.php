<?php

namespace App\Services\OpenIdService;

use App\Services\OpenIdService\Models\OpenIdSession;
use Exception;
use Throwable;

class OpenIdException extends Exception
{
    protected ?string $openIdError = null;
    protected ?OpenIdSession $openIdSession = null;

    /**
     * @param string $openIdError
     * @param string $message
     * @param Throwable|null $previous
     * @param OpenIdSession|null $openIdSession
     * @return static
     */
    public static function withOpenIdError(
        string $openIdError,
        string $message = '',
        ?Throwable $previous = null,
        ?OpenIdSession $openIdSession = null
    ): static {
        $exception = new static($message, 0, $previous);
        $exception->openIdError = $openIdError;
        $exception->openIdSession = $openIdSession;

        return $exception;
    }

    /**
     * @return string|null
     */
    public function getOpenIdError(): ?string
    {
        return $this->openIdError;
    }

    /**
     * @return OpenIdSession|null
     */
    public function getOpenIdSession(): ?OpenIdSession
    {
        return $this->openIdSession;
    }
}
