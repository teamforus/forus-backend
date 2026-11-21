<?php

namespace App\Services\IdentityProviderService\Responses;

use App\Services\IdentityProviderService\Models\IdentityProviderOidcSession;
use Illuminate\Http\JsonResponse;

class IdentityProviderOidcStartResponse extends JsonResponse
{
    /**
     * @param IdentityProviderOidcSession $session
     * @param ?string $browserToken
     */
    public function __construct(IdentityProviderOidcSession $session, ?string $browserToken = null)
    {
        parent::__construct([
            'data' => [
                ...$browserToken !== null ? [
                    'session_uid' => $session->uid,
                    'browser_token' => $browserToken,
                ] : [],
                'redirect_url' => $session->authorization_url,
            ],
        ], 201, ['Cache-Control' => 'no-store']);
    }
}
