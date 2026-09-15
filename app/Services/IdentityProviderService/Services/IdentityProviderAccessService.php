<?php

namespace App\Services\IdentityProviderService\Services;

use App\Models\Employee;
use App\Models\Organization;
use App\Services\IdentityProviderService\Exceptions\IdentityProviderErrorCode;
use App\Services\IdentityProviderService\Exceptions\IdentityProviderException;
use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use App\Services\IdentityProviderService\Models\IdentityProviderMembership;
use App\Services\IdentityProviderService\Models\IdentityProviderOidcSession;
use Illuminate\Support\Facades\Config;

class IdentityProviderAccessService
{
    /**
     * @return bool
     */
    public function isLoginConfigured(): bool
    {
        return Config::get('identity_providers.enabled') &&
            Config::get('identity_providers.entra.client_id') &&
            Config::get('identity_providers.entra.client_secret') &&
            Config::get('identity_providers.entra.redirect_url');
    }

    /**
     * @return void
     */
    public function assertModuleEnabled(): void
    {
        if (!Config::get('identity_providers.enabled')) {
            throw new IdentityProviderException(
                IdentityProviderErrorCode::FEATURE_DISABLED,
                __('exceptions.identity_providers.feature_disabled'),
            );
        }
    }

    /**
     * @param Organization $organization
     * @return void
     */
    public function assertSsoAllowed(Organization $organization): void
    {
        $this->assertModuleEnabled();

        if (!$organization->allowsIdentityProviderSso()) {
            throw new IdentityProviderException(
                IdentityProviderErrorCode::ORGANIZATION_IDENTITY_PROVIDER_DISABLED,
                __('exceptions.identity_providers.organization_identity_provider_disabled'),
            );
        }
    }

    /**
     * @param IdentityProviderConnection $connection
     * @return void
     */
    public function assertSsoActive(IdentityProviderConnection $connection): void
    {
        $this->assertSsoAllowed($connection->organization);

        if (!$connection->isEnabled()) {
            throw new IdentityProviderException(
                IdentityProviderErrorCode::CONNECTION_DISABLED,
                __('exceptions.identity_providers.connection_disabled'),
            );
        }
    }

    /**
     * @param IdentityProviderOidcSession $session
     * @param IdentityProviderConnection $connection
     * @param IdentityProviderMembership $membership
     * @param ?Employee $employee
     * @return void
     */
    public function assertLoginAllowed(
        IdentityProviderOidcSession $session,
        IdentityProviderConnection $connection,
        IdentityProviderMembership $membership,
        ?Employee $employee,
    ): void {
        $this->assertSsoActive($connection);

        if (
            !$membership->isClaimed() ||
            !$membership->identity_id ||
            !$employee ||
            $employee->trashed() ||
            $employee->organization_id !== $connection->organization_id ||
            $employee->identity_address !== $membership->identity->address ||
            $membership->external_identity?->tenant_id !== $connection->tenant_id ||
            $connection->organization->identity_address === $membership->identity->address
        ) {
            throw new IdentityProviderException(
                IdentityProviderErrorCode::MEMBERSHIP_INACTIVE,
                'The managed Entra membership is inactive.',
            );
        }

        if (
            $session->mode !== IdentityProviderOidcSession::MODE_WEBSHOP &&
            !$employee->roles()->exists()
        ) {
            throw new IdentityProviderException(IdentityProviderErrorCode::DASHBOARD_ROLE_MISSING, 'The employee has no dashboard role.');
        }

        if ($session->mode === IdentityProviderOidcSession::MODE_WEBSHOP) {
            $implementation = $session->implementation;

            if (
                !$implementation?->entraLoginEnabled() ||
                $implementation->organization_id !== $connection->organization_id
            ) {
                throw new IdentityProviderException(
                    IdentityProviderErrorCode::WEBSHOP_NOT_ENABLED,
                    'Entra login is disabled for this webshop.',
                );
            }
        }
    }
}
