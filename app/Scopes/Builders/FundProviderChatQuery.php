<?php


namespace App\Scopes\Builders;

use Illuminate\Database\Eloquent\Builder;

class FundProviderChatQuery
{
    /**
     * @param Builder $query
     * @param array|string|int $products
     * @return Builder
     */
    public static function whereProductFilter(Builder $query, $products) {
        return $query->whereIn('product_id', (array) $products);
    }

    /**
     * @param Builder $query
     * @param array|string|int $organizations
     * @return mixed
     */
    public static function whereProviderOrganizationFilter(
        Builder $query,
        $organizations
    ) {
        return $query->whereHas('fund_provider', function(
            Builder $builder
        ) use ($organizations) {
            $builder->whereIn('organization_id', (array) $organizations);
        });
    }

    /**
     * @param Builder $query
     * @param array|string|int $products
     * @param array|string|int $organizations
     * @return mixed
     */
    public static function whereProductAndProviderOrganizationFilter(
        Builder $query,
        $products,
        $organizations
    ) {
        return self::whereProviderOrganizationFilter(
            self::whereProductFilter($query, $products),
            $organizations
        );
    }
}