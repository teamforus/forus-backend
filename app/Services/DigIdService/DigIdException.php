<?php

namespace App\Services\DigIdService;

use Exception;
use Throwable;

class DigIdException extends Exception
{
    protected ?string $digIdErrorCode = null;

    /**
     * @param string $message
     * @param string|null $errorCode
     * @param Throwable|null $previous
     * @return self
     */
    public static function make(string $message, ?string $errorCode = null, ?Throwable $previous = null): self
    {
        $exception = new self($message, 0, $previous);

        if ($errorCode) {
            return $exception->setDigIdCode($errorCode);
        }

        return $exception;
    }

    /**
     * @return string|null
     */
    public function getDigIdCode(): ?string
    {
        return $this->digIdErrorCode;
    }

    /**
     * @param string|null $errorCode
     * @return $this
     */
    public function setDigIdCode(?string $errorCode): DigIdException
    {
        $this->digIdErrorCode = $errorCode;

        return $this;
    }
}
