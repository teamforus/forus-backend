<?php

namespace App\Services\OpenIdService\VerId;

use App\Services\OpenIdService\OpenIdException;
use Throwable;

class VerIdIntentCreationException extends OpenIdException
{
    public const string MESSAGE = 'Unable to create Ver.id authentication intent.';

    /**
     * @param Throwable|null $previous
     */
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct(self::MESSAGE, 0, $previous);
    }
}
