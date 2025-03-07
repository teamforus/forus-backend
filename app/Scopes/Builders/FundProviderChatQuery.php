<?php

namespace App\Scopes\Builders;

use App\Models\FundProviderChat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class FundProviderChatQuery
{
    /**
     * @param Builder|Relation|FundProviderChat $builder
     * @param int|array $products
     * @return Builder|Relation|FundProviderChat
     */
    public static function whereProductFilter(
        Builder|Relation|FundProviderChat $builder,
        int|array $products,
    ): Builder|Relation|FundProviderChat {
        return $builder->whereIn('product_id', (array) $products);
    }

    /**
     * @param Builder|Relation|FundProviderChat $builder
     * @param int|array $organizations
     * @return Builder|Relation|FundProviderChat
     */
    public static function whereProviderOrganizationFilter(
        Builder|Relation|FundProviderChat $builder,
        int|array $organizations,
    ): Builder|Relation|FundProviderChat {
        return $builder->whereHas('fund_provider', function (Builder $builder) use ($organizations) {
            $builder->whereIn('organization_id', (array) $organizations);
        });
    }

    /**
     * @param Builder|Relation|FundProviderChat $builder
     * @param int|array $products
     * @param int|array $organizations
     * @return Builder|Relation|FundProviderChat
     */
    public static function whereProductAndProviderOrganizationFilter(
        Builder|Relation|FundProviderChat $builder,
        int|array $products,
        int|array $organizations,
    ): Builder|Relation|FundProviderChat {
        return self::whereProviderOrganizationFilter(
            self::whereProductFilter($builder, $products),
            $organizations,
        );
    }
}
