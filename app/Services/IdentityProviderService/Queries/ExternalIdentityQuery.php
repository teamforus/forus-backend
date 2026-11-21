<?php

namespace App\Services\IdentityProviderService\Queries;

use App\Services\IdentityProviderService\Models\ExternalIdentity;
use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class ExternalIdentityQuery
{
    /**
     * @param Builder|Relation|ExternalIdentity $query
     * @param string $provider
     * @param string $tenantId
     * @return Builder|Relation|ExternalIdentity
     */
    public static function whereForTenant(
        Builder|Relation|ExternalIdentity $query,
        string $provider,
        string $tenantId,
    ): Builder|Relation|ExternalIdentity {
        return $query->where('provider', $provider)->where('tenant_id', $tenantId);
    }

    /**
     * @param Builder|Relation|ExternalIdentity $query
     * @param string $tenantId
     * @param string $objectId
     * @return Builder|Relation|ExternalIdentity
     */
    public static function whereEntraObject(
        Builder|Relation|ExternalIdentity $query,
        string $tenantId,
        string $objectId,
    ): Builder|Relation|ExternalIdentity {
        return self::whereForTenant($query, IdentityProviderConnection::PROVIDER_ENTRA, $tenantId)
            ->where('object_id', $objectId);
    }
}
