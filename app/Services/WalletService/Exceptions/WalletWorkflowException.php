<?php

namespace App\Services\WalletService\Exceptions;

use App\Services\OpenIdService\OpenIdException;
use App\Services\WalletService\Models\WalletSession;
use Throwable;

class WalletWorkflowException extends OpenIdException
{
    public const string ERROR_UNKNOWN = 'wallet_unknown_error';
    public const string ERROR_NOT_ENABLED = 'not_enabled';
    public const string ERROR_SESSION_EXPIRED = 'session_expired';
    public const string ERROR_CALLBACK_FAILED = 'callback_failed';
    public const string ERROR_MISSING_CLAIMS = 'missing_claims';
    public const string ERROR_UID_NOT_FOUND = 'uid_not_found';
    public const string ERROR_UID_DONT_MATCH = 'uid_dont_match';
    public const string ERROR_UID_USED = 'uid_used';
    public const string ERROR_UNKNOWN_SESSION_TYPE = 'unknown_session_type';
    public const string ERROR_DISCLOSURE_INVALID = 'disclosure_invalid';
    public const string ERROR_DISCLOSURE_CANCELLED = 'disclosure_cancelled';

    protected ?string $errorCode = null;
    protected ?WalletSession $walletSession = null;

    /**
     * @param string $errorCode
     * @param string $message
     * @param Throwable|null $previous
     * @param WalletSession|null $walletSession
     * @return static
     */
    public static function withError(
        string $errorCode,
        string $message = '',
        ?Throwable $previous = null,
        ?WalletSession $walletSession = null
    ): static {
        $exception = new static($message, 0, $previous);
        $exception->errorCode = $errorCode;
        $exception->walletSession = $walletSession;

        return $exception;
    }

    /**
     * @return string|null
     */
    public function errorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * @return WalletSession|null
     */
    public function session(): ?WalletSession
    {
        return $this->walletSession;
    }
}
