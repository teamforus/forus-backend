<?php

namespace App\Services\IdentityProviderService\Queries;

use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use App\Services\IdentityProviderService\Models\IdentityProviderMembership;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class IdentityProviderMembershipQuery
{
    /**
     * @param IdentityProviderConnection $connection
     * @param bool $withDeleted
     * @return Builder<IdentityProviderMembership>
     */
    public static function forScimConnection(IdentityProviderConnection $connection, bool $withDeleted = false): Builder
    {
        return IdentityProviderMembership::where('connection_id', $connection->id)
            ->where('account_type', IdentityProviderMembership::ACCOUNT_TYPE_REQUESTER)
            ->where('claim_state', IdentityProviderMembership::CLAIM_CLAIMED)
            ->whereIn('provisioning_status', [
                IdentityProviderMembership::PROVISIONING_STATUS_ACTIVE,
                IdentityProviderMembership::PROVISIONING_STATUS_DISABLED,
                ...$withDeleted ? [IdentityProviderMembership::PROVISIONING_STATUS_DELETED] : [],
            ])
            ->whereHas('external_identity', fn (Builder $query) => $query->where([
                'provider' => $connection->provider,
                'tenant_id' => $connection->tenant_id,
            ]));
    }

    /**
     * @param Builder|Relation|IdentityProviderMembership $query
     * @param array{attribute: string, value: string}|null $filter
     * @return Builder|Relation|IdentityProviderMembership
     */
    public static function whereScimFilter(
        Builder|Relation|IdentityProviderMembership $query,
        ?array $filter,
    ): Builder|Relation|IdentityProviderMembership {
        if ($filter === null) {
            return $query;
        }

        $value = $filter['value'];

        return match ($filter['attribute']) {
            'externalid' => $query->whereRelation('external_identity', 'object_id', strtolower($value)),
            'username' => $query->whereRaw('LOWER(scim_user_name) = ?', [mb_strtolower($value)]),
            'id' => $query->where('uid', $value),
        };
    }

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
