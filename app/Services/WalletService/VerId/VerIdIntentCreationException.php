<?php

namespace App\Services\WalletService\VerId;

use App\Services\WalletService\Exceptions\WalletWorkflowException;
use Throwable;

class VerIdIntentCreationException extends WalletWorkflowException
{
    public const string MESSAGE = 'Unable to create Ver.id intent.';

    /**
     * @param Throwable|null $previous
     */
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct(self::MESSAGE, 0, $previous);
    }
}
