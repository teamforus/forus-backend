<?php

namespace App\Services\IdentityProviderService\Queries;

use App\Models\Identity;
use App\Scopes\Builders\OrganizationQuery;
use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class IdentityProviderConnectionQuery
{
    /**
     * @param Builder|Relation|IdentityProviderConnection $query
     * @param string $tenantId
     * @return Builder|Relation|IdentityProviderConnection
     */
    public static function whereCurrentEntraTenant(
        Builder|Relation|IdentityProviderConnection $query,
        string $tenantId,
    ): Builder|Relation|IdentityProviderConnection {
        return $query
            ->where('provider', IdentityProviderConnection::PROVIDER_ENTRA)
            ->where('tenant_id', $tenantId)
            ->where('status', '!=', IdentityProviderConnection::STATUS_DISCONNECTED);
    }

    /**
     * @param Builder|Relation|IdentityProviderConnection $query
     * @param Identity $identity
     * @return Builder|Relation|IdentityProviderConnection
     */
    public static function whereAvailableForSelfLink(
        Builder|Relation|IdentityProviderConnection $query,
        Identity $identity,
    ): Builder|Relation|IdentityProviderConnection {
        return $query
            ->where('provider', IdentityProviderConnection::PROVIDER_ENTRA)
            ->where('status', IdentityProviderConnection::STATUS_ENABLED)
            ->whereHas('organization', fn (Builder $query) =>
                OrganizationQuery::whereEligibleForIdentityProviderLink($query, $identity))
            ->whereDoesntHave('memberships', fn (Builder $query) =>
                IdentityProviderMembershipQuery::whereClaimedForIdentity($query, $identity->id));
    }
}
