<?php

namespace App\Services\IdentityProviderService\Data;

readonly class IdentityProviderAuthorizationData
{
    /**
     * @param string $redirectUrl
     * @param string $state
     * @param string $nonce
     * @param string $codeVerifier
     */
    public function __construct(
        public string $redirectUrl,
        public string $state,
        public string $nonce,
        public string $codeVerifier,
    ) {
    }
}
