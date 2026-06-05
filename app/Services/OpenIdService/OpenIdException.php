<?php

namespace App\Services\OpenIdService;

use App\Services\OpenIdService\Models\OpenIdSession;
use Exception;
use Throwable;

class OpenIdException extends Exception
{
    public const string ERROR_UNKNOWN = 'openid_unknown_error';
    public const string ERROR_NOT_ENABLED = 'not_enabled';
    public const string ERROR_SESSION_EXPIRED = 'session_expired';
    public const string ERROR_CALLBACK_FAILED = 'callback_failed';
    public const string ERROR_MISSING_CLAIMS = 'missing_claims';
    public const string ERROR_UID_NOT_FOUND = 'uid_not_found';
    public const string ERROR_UID_DONT_MATCH = 'uid_dont_match';
    public const string ERROR_UID_USED = 'uid_used';
    public const string ERROR_UNKNOWN_SESSION_TYPE = 'unknown_session_type';

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
