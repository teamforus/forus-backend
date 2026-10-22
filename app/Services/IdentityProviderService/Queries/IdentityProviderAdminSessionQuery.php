<?php

namespace App\Services\IdentityProviderService\Queries;

use App\Services\IdentityProviderService\Models\IdentityProviderAdminSession;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class IdentityProviderAdminSessionQuery
{
    /**
     * @param Builder|Relation|IdentityProviderAdminSession $query
     * @return Builder|Relation|IdentityProviderAdminSession
     */
    public static function whereUnfinished(
        Builder|Relation|IdentityProviderAdminSession $query,
    ): Builder|Relation|IdentityProviderAdminSession {
        return $query->whereIn('status', [
            IdentityProviderAdminSession::STATUS_VERIFYING_TENANT,
            IdentityProviderAdminSession::STATUS_AWAITING_CONSENT,
        ]);
    }

    /**
     * @param Builder|Relation|IdentityProviderAdminSession $query
     * @param DateTimeInterface $cutoff
     * @return Builder|Relation|IdentityProviderAdminSession
     */
    public static function wherePrunable(
        Builder|Relation|IdentityProviderAdminSession $query,
        DateTimeInterface $cutoff,
    ): Builder|Relation|IdentityProviderAdminSession {
        return $query->where('expires_at', '<', $cutoff)->whereIn('status', [
            IdentityProviderAdminSession::STATUS_CONFIRMED,
            IdentityProviderAdminSession::STATUS_ERROR,
            IdentityProviderAdminSession::STATUS_EXPIRED,
        ]);
    }
}
