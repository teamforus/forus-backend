<?php

namespace App\Services\IdentityProviderService\Implementations;

use App\Services\IdentityProviderService\Contracts\IdentityProviderAdapterContract;
use App\Services\IdentityProviderService\Data\IdentityProviderAccountData;
use App\Services\IdentityProviderService\Data\IdentityProviderAuthorizationData;
use App\Services\IdentityProviderService\Exceptions\IdentityProviderErrorCode;
use App\Services\IdentityProviderService\Exceptions\IdentityProviderException;
use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use App\Services\OpenIdService\OpenIdClientService;
use App\Services\OpenIdService\OpenIdException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class EntraAdapter implements IdentityProviderAdapterContract
{
    protected const string ORGANIZATIONS_TENANT = 'organizations';

    /**
     * @param OpenIdClientService $openIdClient
     */
    public function __construct(protected OpenIdClientService $openIdClient)
    {
    }

    /**
     * @param ?string $tenantId
     * @throws OpenIdException
     * @return IdentityProviderAuthorizationData
     */
    public function buildAuthorization(?string $tenantId = null): IdentityProviderAuthorizationData
    {
        $authorization = $this->openIdClient->buildAuthorization(
            $this->oidcConfig($tenantId ?: self::ORGANIZATIONS_TENANT),
            ['prompt' => 'select_account'],
        );

        return new IdentityProviderAuthorizationData(
            $authorization['redirect_url'],
            $authorization['state'],
            $authorization['nonce'],
            $authorization['code_verifier'],
        );
    }

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
    ): IdentityProviderAccountData {
        $payload = $this->openIdClient->resolveCallback(
            $this->oidcConfig($tenantId ?: self::ORGANIZATIONS_TENANT),
            $context,
            $request,
        );

        $claims = $payload['claims'];
        $tenantId = strtolower((string) ($claims['tid'] ?? ''));
        $objectId = strtolower((string) ($claims['oid'] ?? ''));
        $issuer = (string) ($claims['iss'] ?? '');
        $subject = (string) ($claims['sub'] ?? '');
        $accountType = array_key_exists('acct', $claims) ? (string) $claims['acct'] : null;

        if (!Str::isUuid($tenantId) || !Str::isUuid($objectId) || $issuer === '' || $subject === '') {
            throw new IdentityProviderException(IdentityProviderErrorCode::CLAIMS_INVALID, 'Required stable Entra claims are missing.');
        }

        if ($accountType === null) {
            throw new IdentityProviderException(
                IdentityProviderErrorCode::ENTRA_ACCOUNT_STATUS_MISSING,
                'The Entra ID token has no tenant account status claim.',
            );
        }

        if ($accountType === '1') {
            throw new IdentityProviderException(
                IdentityProviderErrorCode::ENTRA_GUEST_NOT_SUPPORTED,
                'Guest Entra accounts are not supported.',
            );
        }

        if ($accountType !== '0') {
            throw new IdentityProviderException(
                IdentityProviderErrorCode::ENTRA_ACCOUNT_STATUS_INVALID,
                'The Entra ID token has an invalid tenant account status claim.',
                context: ['account_status' => $accountType],
            );
        }

        return new IdentityProviderAccountData(
            IdentityProviderConnection::PROVIDER_ENTRA,
            $tenantId,
            $objectId,
            $issuer,
            $subject,
        );
    }

    /**
     * @param Request $request
     * @param array{state: string, nonce: ?string, code_verifier: ?string} $context
     * @throws OpenIdException|IdentityProviderException
     * @return array{tenant_id: string, object_id: string}
     */
    public function resolveAdministrator(Request $request, array $context): array
    {
        $payload = $this->openIdClient->resolveCallback($this->oidcConfig(), $context, $request);
        $tenantId = $payload['claims']['tid'] ?? null;
        $objectId = $payload['claims']['oid'] ?? null;

        if (!is_string($tenantId) || !Str::isUuid($tenantId)) {
            throw new OpenIdException('The Entra ID token has no valid tenant ID.');
        }

        if (!is_string($objectId) || !Str::isUuid($objectId)) {
            throw new OpenIdException('The Entra ID token has no valid object ID.');
        }

        $roleIds = array_map(
            fn (mixed $roleId) => strtolower((string) $roleId),
            is_array($payload['claims']['wids'] ?? null) ? $payload['claims']['wids'] : [],
        );

        $roleClaimPresent = array_key_exists('wids', $payload['claims']);
        $allowedRoleIds = array_map('strtolower', Config::get('identity_providers.entra.admin_role_ids', []));

        if (array_intersect($roleIds, $allowedRoleIds) === []) {
            throw new IdentityProviderException(
                $roleClaimPresent
                    ? IdentityProviderErrorCode::ENTRA_ADMIN_ROLE_REQUIRED
                    : IdentityProviderErrorCode::ENTRA_ROLE_CLAIM_MISSING,
                $roleClaimPresent
                    ? 'The Entra account does not have a supported tenant administrator role.'
                    : 'The Entra ID token has no tenant administrator role claim.',
                context: [
                    'tenant_id' => strtolower($tenantId),
                    'role_claim_present' => $roleClaimPresent,
                    'received_role_ids' => $roleIds,
                ],
            );
        }

        return ['tenant_id' => strtolower($tenantId), 'object_id' => strtolower($objectId)];
    }

    /**
     * @param Request $request
     * @param ?string $expectedTenantId
     * @throws IdentityProviderException
     * @return void
     */
    public function validateAdminConsent(Request $request, ?string $expectedTenantId): void
    {
        $tenantId = $request->query('tenant');
        $adminConsent = strtolower((string) $request->query('admin_consent')) === 'true';

        $errorCode = match (true) {
            !$adminConsent => IdentityProviderErrorCode::ADMIN_CONSENT_DENIED,
            !is_string($tenantId) || !Str::isUuid($tenantId) => IdentityProviderErrorCode::ADMIN_CONSENT_TENANT_MISSING,
            !hash_equals((string) $expectedTenantId, strtolower($tenantId)) => IdentityProviderErrorCode::ADMIN_CONSENT_TENANT_MISMATCH,
            default => null,
        };

        if ($errorCode) {
            throw new IdentityProviderException(
                $errorCode,
                match ($errorCode) {
                    IdentityProviderErrorCode::ADMIN_CONSENT_DENIED => 'Microsoft Entra administrator consent was not granted.',
                    IdentityProviderErrorCode::ADMIN_CONSENT_TENANT_MISSING => 'Microsoft Entra did not return a valid tenant.',
                    default => 'Microsoft Entra returned a different tenant than the verified tenant.',
                },
                context: [
                    'tenant_id' => is_string($tenantId) && Str::isUuid($tenantId) ? strtolower($tenantId) : null,
                    'provider_error_code' => $request->query('error'),
                    'admin_consent' => $adminConsent,
                    'tenant_present' => is_string($tenantId) && $tenantId !== '',
                ],
            );
        }
    }

    /**
     * @param string $tenant
     * @return string
     */
    public function issuer(string $tenant): string
    {
        return sprintf('https://login.microsoftonline.com/%s/v2.0', $tenant);
    }

    /**
     * @param string $tenant
     * @param string $state
     * @return string
     */
    public function adminConsentUrl(string $tenant, string $state): string
    {
        $params = [
            'client_id' => Config::get('identity_providers.entra.client_id'),
            'scope' => implode(' ', Config::get('identity_providers.entra.scopes', [])),
            'redirect_uri' => Config::get('identity_providers.entra.admin_consent_redirect_url'),
            'state' => $state,
        ];

        return sprintf(
            'https://login.microsoftonline.com/%s/v2.0/adminconsent?%s',
            $tenant,
            http_build_query($params, '', '&', PHP_QUERY_RFC3986),
        );
    }

    /**
     * @param string $tenant
     * @return array
     */
    protected function oidcConfig(string $tenant = self::ORGANIZATIONS_TENANT): array
    {
        return [
            ...Config::get('identity_providers.entra', []),
            'issuer' => $this->issuer($tenant),
            'aad_issuer_validation' => $tenant === self::ORGANIZATIONS_TENANT,
            'auth_params' => [],
        ];
    }
}
