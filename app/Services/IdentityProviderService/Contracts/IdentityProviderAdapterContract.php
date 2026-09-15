<?php

namespace App\Services\IdentityProviderService\Contracts;

use App\Services\IdentityProviderService\Data\IdentityProviderAccountData;
use App\Services\IdentityProviderService\Data\IdentityProviderAuthorizationData;
use App\Services\IdentityProviderService\Exceptions\IdentityProviderException;
use App\Services\OpenIdService\OpenIdException;
use Illuminate\Http\Request;

interface IdentityProviderAdapterContract
{
    /**
     * @param ?string $tenantId
     * @throws OpenIdException
     * @return IdentityProviderAuthorizationData
     */
    public function buildAuthorization(?string $tenantId = null): IdentityProviderAuthorizationData;

    /**
     * @param Request $request
     * @param array{state: string, nonce: ?string, code_verifier: ?string} $context
     * @param ?string $tenantId
     * @throws OpenIdException|IdentityProviderException
     * @return IdentityProviderAccountData
     */
    public function resolveAccount(
        Request $request,
        array $context,
        ?string $tenantId = null,
    ): IdentityProviderAccountData;
}
