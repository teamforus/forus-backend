<?php

namespace App\Services\IdentityProviderService\Queries;

use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use App\Services\IdentityProviderService\Models\IdentityProviderMembership;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class IdentityProviderMembershipQuery
{
    /**
     * @param Builder|Relation|IdentityProviderMembership $query
     * @return Builder|Relation|IdentityProviderMembership
     */
    public static function whereClaimed(
        Builder|Relation|IdentityProviderMembership $query,
    ): Builder|Relation|IdentityProviderMembership {
        return $query->where('claim_state', IdentityProviderMembership::CLAIM_CLAIMED);
    }

    /**
     * @param Builder|Relation|IdentityProviderMembership $query
     * @param int $identityId
     * @return Builder|Relation|IdentityProviderMembership
     */
    public static function whereClaimedForIdentity(
        Builder|Relation|IdentityProviderMembership $query,
        int $identityId,
    ): Builder|Relation|IdentityProviderMembership {
        return self::whereClaimed($query->where('identity_id', $identityId));
    }

    /**
     * @param Builder|Relation|IdentityProviderMembership $query
     * @return Builder|Relation|IdentityProviderMembership
     */
    public static function whereCurrent(
        Builder|Relation|IdentityProviderMembership $query,
    ): Builder|Relation|IdentityProviderMembership {
        return $query
            ->where('claim_state', '!=', IdentityProviderMembership::CLAIM_RETIRED)
            ->whereHas('external_identity', fn (Builder $query) => $query->whereExists(
                IdentityProviderConnection::select('identity_provider_connections.id')
                    ->whereColumn([
                        ['identity_provider_connections.id', 'identity_provider_memberships.connection_id'],
                        ['identity_provider_connections.provider', 'external_identities.provider'],
                        ['identity_provider_connections.tenant_id', 'external_identities.tenant_id'],
                    ]),
            ));
    }
}
