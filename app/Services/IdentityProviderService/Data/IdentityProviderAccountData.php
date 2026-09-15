<?php

namespace App\Services\IdentityProviderService\Data;

readonly class IdentityProviderAccountData
{
    /**
     * @param string $provider
     * @param string $tenantId
     * @param string $externalAccountId
     * @param string $issuer
     * @param string $subject
     */
    public function __construct(
        public string $provider,
        public string $tenantId,
        public string $externalAccountId,
        public string $issuer,
        public string $subject,
    ) {
    }
}
