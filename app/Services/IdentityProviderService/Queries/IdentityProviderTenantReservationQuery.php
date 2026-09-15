<?php

namespace App\Services\IdentityProviderService\Queries;

use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use App\Services\IdentityProviderService\Models\IdentityProviderTenantReservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class IdentityProviderTenantReservationQuery
{
    /**
     * @param Builder|Relation|IdentityProviderTenantReservation $query
     * @param string $tenantId
     * @return Builder|Relation|IdentityProviderTenantReservation
     */
    public static function whereEntraTenant(
        Builder|Relation|IdentityProviderTenantReservation $query,
        string $tenantId,
    ): Builder|Relation|IdentityProviderTenantReservation {
        return $query
            ->where('provider', IdentityProviderConnection::PROVIDER_ENTRA)
            ->where('tenant_id', $tenantId);
    }
}
