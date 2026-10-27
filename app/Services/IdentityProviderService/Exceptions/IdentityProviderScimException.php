<?php

namespace App\Services\IdentityProviderService\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use RuntimeException;
use Throwable;

class IdentityProviderScimException extends RuntimeException implements ShouldntReport
{
    /**
     * @param string $message
     * @param int $status
     * @param string|null $scimType
     * @param string|null $reason
     * @param string|null $diagnosticId
     * @param Throwable|null $previous
     * @param array<string, array<string>> $validationErrors
     */
    public function __construct(
        string $message,
        public readonly int $status = 400,
        public readonly ?string $scimType = null,
        public readonly ?string $reason = null,
        public readonly ?string $diagnosticId = null,
        ?Throwable $previous = null,
        public readonly array $validationErrors = [],
    ) {
        parent::__construct($message, previous: $previous);
    }
}
