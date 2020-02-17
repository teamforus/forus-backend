<?php


namespace App\Scopes\Builders;

use Illuminate\Database\Eloquent\Builder;

class FundProviderQuery
{
    /**
     * @param Builder $query
     * @param $fund_id
     * @param null $type
     * @param null $product_id
     * @return Builder
     */
    public static function whereApprovedForFundsFilter(
        Builder $query,
        $fund_id,
        $type = null,
        $product_id = null
    ) {
        return $query->where(function(Builder $builder) use ($fund_id, $type, $product_id) {
            $builder->whereIn('fund_id', (array) $fund_id);

            $builder->where(function(Builder $builder) use ($type, $product_id) {
                if ($type == null) {
                    $builder->where('allow_budget', true);
                    $builder->orWhere('allow_products', true);

                    if ($product_id) {
                        $builder->orWhereHas('fund_provider_products', function(
                            Builder $builder
                        ) use ($product_id) {
                            $builder->whereIn('fund_provider_products.id', (array) $product_id);
                        });
                    } else {
                        $builder->orWhereHas('fund_provider_products');
                    }
                } else if ($type == 'budget') {
                    $builder->where('allow_budget', true);
                } else if ($type == 'product') {
                    $builder->where('allow_products', true);

                    if ($product_id) {
                        $builder->orWhereHas('fund_provider_products', function(
                            Builder $builder
                        ) use ($product_id) {
                            $builder->whereIn('fund_provider_products.id', (array) $product_id);
                        });
                    } else {
                        $builder->orWhereHas('fund_provider_products');
                    }
                }
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