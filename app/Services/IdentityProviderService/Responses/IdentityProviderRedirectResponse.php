<?php

namespace App\Services\IdentityProviderService\Responses;

use App\Services\IdentityProviderService\Models\IdentityProviderAdminSession;
use App\Services\IdentityProviderService\Models\IdentityProviderOidcSession;
use Illuminate\Http\RedirectResponse;

class IdentityProviderRedirectResponse extends RedirectResponse
{
    /**
     * @param string $url
     */
    public function __construct(string $url)
    {
        parent::__construct($url, 302, [
            'Referrer-Policy' => 'no-referrer',
            'Cache-Control' => 'no-store',
        ]);
    }

    /**
     * @param IdentityProviderAdminSession $session
     * @param array $params
     * @return self
     */
    public static function forAdminSession(IdentityProviderAdminSession $session, array $params): self
    {
        return new self(url_extend_get_params($session->final_url, $params));
    }

    /**
     * @param string $error
     * @param ?string $diagnosticId
     * @return self
     */
    public static function fallback(string $error, ?string $diagnosticId = null): self
    {
        return new self(url_extend_get_params(url('/'), self::failureParams($error, $diagnosticId)));
    }

    /**
     * @param string $errorCode
     * @param ?string $diagnosticId
     * @param ?IdentityProviderOidcSession $session
     * @return array
     */
    public static function failureParams(
        string $errorCode,
        ?string $diagnosticId,
        ?IdentityProviderOidcSession $session = null,
    ): array {
        return array_filter([
            'entra_error' => $errorCode,
            'entra_error_ref' => $diagnosticId,
            'entra_session' => $session?->uid,
        ]);
    }

    /**
     * @param IdentityProviderOidcSession $session
     * @param string $errorCode
     * @param ?string $diagnosticId
     * @return self
     */
    public static function forSessionFailure(
        IdentityProviderOidcSession $session,
        string $errorCode,
        ?string $diagnosticId,
    ): self {
        return new self(url_extend_get_params($session->final_url, self::failureParams(
            $errorCode,
            $diagnosticId,
            $session,
        )));
    }
}
