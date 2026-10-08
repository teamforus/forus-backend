<?php

namespace App\Services\IdentityProviderService\Resources;

use App\Http\Resources\BaseJsonResource;
use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use App\Services\IdentityProviderService\Models\IdentityProviderMembership;
use Illuminate\Http\Request;

/**
 * @property-read IdentityProviderConnection|null $resource
 */
class IdentityProviderConnectionResource extends BaseJsonResource
{
    public const array LOAD = [
        'memberships_current_claimed',
        'organization.implementations',
        'latest_scim_credential',
    ];

    public const array LOAD_COUNT = [
        'memberships_requesters',
    ];

    /**
     * @param Request $request
     * @return array|null
     */
    public function toArray(Request $request): ?array
    {
        if (!$this->resource) {
            return null;
        }

        $connection = $this->resource;

        return [
            ...$connection->only(['uid', 'provider', 'tenant_id', 'issuer', 'status', 'last_auth_failure_code']),
            ...$this->makeTimestamps($connection->only([
                'consented_at', 'enabled_at', 'paused_at', 'disconnected_at',
                'last_auth_success_at', 'last_auth_failure_at',
            ])),
            'managed_employees_count' => $connection->memberships_current_claimed
                ->where('account_type', IdentityProviderMembership::ACCOUNT_TYPE_EMPLOYEE)->count(),
            'can_disconnect' => !$connection->isDisconnected() &&
                $connection->memberships_requesters_count === 0,
            'disconnect_disabled_reason' => $connection->memberships_requesters_count > 0
                ? __('exceptions.identity_providers.connection_has_requesters')
                : null,
            'scim_url' => $connection->scimUrl(),
            'scim_credential' => $connection->latest_scim_credential
                ? IdentityProviderScimCredentialResource::make($connection->latest_scim_credential)
                : null,
            'webshops' => $connection->organization->implementations
                ->whereNotNull('url_webshop')
                ->map->only(['id', 'name', 'url_webshop', 'entra_login_enabled'])->values(),
        ];
    }
}
