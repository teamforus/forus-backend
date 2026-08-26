<?php

namespace App\Services\PdfToImgService\Exceptions;

use Exception;
use Throwable;

class PdfToImgException extends Exception
{
    public const string ERROR_MAX_PAGES_EXCEEDED = 'max_pages_exceeded';

    /**
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     * @param string|null $errorCode
     * @param array $errorParams
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        protected ?string $errorCode = null,
        protected array $errorParams = [],
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string|null
     */
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * @return array
     */
    public function getErrorParams(): array
    {
        return $this->errorParams;
    }
}
