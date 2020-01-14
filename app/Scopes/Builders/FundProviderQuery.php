<?php


namespace App\Scopes\Builders;

use Illuminate\Database\Eloquent\Builder;

class FundProviderQuery
{
    /**
     * @param Builder $query
     * @param $fund_id
     * @return Builder
     */
    public static function whereApprovedForFundsFilter(
        Builder $query,
        $fund_id
    ) {
        return $query->where(function(Builder $builder) use ($fund_id) {
            $builder->whereIn('fund_id', (array) $fund_id);

            $builder->where(function(Builder $builder) use ($fund_id) {
                $builder->where('allow_budget', true);
                $builder->orWhere('allow_products', true);
                $builder->orWhereHas('fund_provider_products');
            });
        });
    }

    /**
     * @param Builder $query
     * @param $fund_id
     * @return Builder
     */
    public static function wherePendingForFundsFilter(
        Builder $query,
        $fund_id
    ) {
        return $query->where(function(Builder $builder) use ($fund_id) {
            $builder->whereIn('fund_id', (array) $fund_id);

            $builder->where(function(Builder $builder) use ($fund_id) {
                $builder->where('allow_budget', false);
                $builder->where('allow_products', false);
                $builder->doesntHave('fund_provider_products');
            });
        });
    }
}