<?php

namespace App\Services\IdentityProviderService\Queries;

use App\Services\IdentityProviderService\Models\IdentityProviderOidcSession;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class IdentityProviderOidcSessionQuery
{
    /**
     * @param Builder|Relation|IdentityProviderOidcSession $query
     * @return Builder|Relation|IdentityProviderOidcSession
     */
    public static function wherePending(
        Builder|Relation|IdentityProviderOidcSession $query,
    ): Builder|Relation|IdentityProviderOidcSession {
        return $query->where('status', IdentityProviderOidcSession::STATUS_PENDING);
    }

    /**
     * @param Builder|Relation|IdentityProviderOidcSession $query
     * @param DateTimeInterface $cutoff
     * @return Builder|Relation|IdentityProviderOidcSession
     */
    public static function wherePrunable(
        Builder|Relation|IdentityProviderOidcSession $query,
        DateTimeInterface $cutoff,
    ): Builder|Relation|IdentityProviderOidcSession {
        return $query->where('expires_at', '<', $cutoff)->whereIn('status', [
            IdentityProviderOidcSession::STATUS_RESOLVED,
            IdentityProviderOidcSession::STATUS_ERROR,
            IdentityProviderOidcSession::STATUS_EXPIRED,
        ]);
    }

    /**
     * @param Builder|Relation|IdentityProviderOidcSession $query
     * @return Builder|Relation|IdentityProviderOidcSession
     */
    public static function whereInvalidatable(
        Builder|Relation|IdentityProviderOidcSession $query,
    ): Builder|Relation|IdentityProviderOidcSession {
        return $query->where(function (Builder $query) {
            self::wherePending($query)->orWhere(function (Builder $query) {
                $query->where('status', IdentityProviderOidcSession::STATUS_RESOLVED)
                    ->whereNotNull('exchange_token_hash')
                    ->whereNull('exchange_consumed_at');
            });
        });
    }
}
