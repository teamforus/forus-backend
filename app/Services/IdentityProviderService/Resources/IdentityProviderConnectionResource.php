<?php

namespace App\Services\IdentityProviderService\Resources;

use App\Http\Resources\BaseJsonResource;
use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use Illuminate\Http\Request;

/**
 * @property-read IdentityProviderConnection|null $resource
 */
class IdentityProviderConnectionResource extends BaseJsonResource
{
    public const array LOAD = [
        'memberships_current_claimed',
        'organization.implementations',
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
            'managed_employees_count' => $connection->memberships_current_claimed->count(),
            'webshops' => $connection->organization->implementations
                ->whereNotNull('url_webshop')
                ->map->only(['id', 'name', 'url_webshop', 'entra_login_enabled'])->values(),
        ];
    }
}
