<?php


namespace App\Scopes\Builders;

use App\Models\FundProvider;
use App\Models\FundProviderUnsubscribe;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class FundProviderUnsubscribeQuery
{
    /**
     * @param Builder|Relation|FundProviderUnsubscribe $builder
     * @return Builder|Relation|FundProviderUnsubscribe
     */
    public static function wherePending(
        Builder|Relation|FundProviderUnsubscribe $builder,
    ): Builder|Relation|FundProviderUnsubscribe {
        return $builder->where(function(Builder $builder) {
            $builder->where('canceled', false);
            $builder->where('unsubscribe_at', '>=', now()->endOfDay());
            $builder->whereRelation('fund_provider', 'state', FundProvider::STATE_ACCEPTED);
        });
    }

    /**
     * @param Builder|Relation|FundProviderUnsubscribe $builder
     * @return Builder|Relation|FundProviderUnsubscribe
     */
    public static function whereOverdue(
        Builder|Relation|FundProviderUnsubscribe $builder,
    ): Builder|Relation|FundProviderUnsubscribe {
        return $builder->where(function(Builder $builder) {
            $builder->where('canceled', false);
            $builder->where('unsubscribe_at', '<', now()->endOfDay());
            $builder->whereRelation('fund_provider', 'state', FundProvider::STATE_ACCEPTED);
        });
    }

    /**
     * @param Builder|Relation|FundProviderUnsubscribe $builder
     * @return Builder|Relation|FundProviderUnsubscribe
     */
    public static function whereApproved(
        Builder|Relation|FundProviderUnsubscribe $builder,
    ): Builder|Relation|FundProviderUnsubscribe {
        return $builder->where(function(Builder $builder) {
            $builder->where('canceled', false);
            $builder->whereDoesntHave('fund_provider', function(Builder $builder) {
                $builder->where('state', FundProvider::STATE_ACCEPTED);
            });
        });
    }

    /**
     * @param Builder|Relation|FundProviderUnsubscribe $builder
     * @return Builder|Relation|FundProviderUnsubscribe
     */
    public static function whereCanceled(
        Builder|Relation|FundProviderUnsubscribe $builder,
    ): Builder|Relation|FundProviderUnsubscribe {
        return $builder->where(function(Builder $builder) {
            $builder->where('canceled', true);
        });
    }
}