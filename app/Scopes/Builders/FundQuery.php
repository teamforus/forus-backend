<?php

namespace App\Scopes\Builders;

use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class FundQuery
{
    /**
     * @param Builder|Relation|Fund $query
     * @return Builder|Relation|Fund
     */
    public static function whereActiveFilter(
        Builder|Relation|Fund $query,
    ): Builder|Relation|Fund {
        return $query
            ->where('state', Fund::STATE_ACTIVE)
            ->whereDate('end_date', '>=', today());
    }

    /**
     * @param Builder|Relation|Fund $query
     * @return Builder|Relation|Fund
     */
    public static function whereNotActiveFilter(
        Builder|Relation|Fund $query,
    ): Builder|Relation|Fund {
        return $query->where(function (Builder $builder) {
            $builder->where('state', '!=', Fund::STATE_ACTIVE);
            $builder->orWhereDate('end_date', '<', today());
        });
    }

    /**
     * @param Builder|Relation|Fund $query
     * @param bool $includeArchived
     * @param bool $includeExternal
     * @return Builder|Relation|Fund
     */
    public static function whereActiveOrClosedFilter(
        Builder|Relation|Fund $query,
        bool $includeArchived = true,
        bool $includeExternal = true,
    ): Builder|Relation|Fund {
        return $query->whereIn('state', [
            Fund::STATE_ACTIVE, Fund::STATE_CLOSED,
        ])->where(function (Builder $builder) use ($includeArchived, $includeExternal) {
            if (!$includeArchived) {
                $builder->where('archived', false);
            }

            if (!$includeExternal) {
                $builder->where('external', false);
            }
        });
    }

    /**
     * @param Builder|Relation|Fund $query
     * @param Product $product
     * @return Builder|Relation|Fund
     * @noinspection PhpUnused
     */
    public static function whereProductsAreApprovedFilter(
        Builder|Relation|Fund $query,
        Product $product,
    ): Builder|Relation|Fund {
        if ($product->sponsor_organization_id) {
            $query->where('organization_id', $product->sponsor_organization_id);
        }

        $query->whereDoesntHave('providers.product_exclusions', static function (Builder $builder) use ($product) {
            $builder->where('product_id', $product->id);
        });

        return $query->where(function (Builder $builder) use ($product) {
            $builder->where(function (Builder $builder) use ($product) {
                $builder->whereHas('providers', static function (Builder $builder) use ($product) {
                    $builder->where('state', FundProvider::STATE_ACCEPTED);
                    $builder->where('allow_products', '=', true);
                    $builder->where('excluded', false);

                    $builder->whereHas('organization.products', static function (Builder $builder) use ($product) {
                        $builder->where('products.id', $product->id);
                    });
                });
            });

            $builder->orWhereHas('providers', static function (Builder $builder) use ($product) {
                $builder->where('state', FundProvider::STATE_ACCEPTED);
                $builder->where('excluded', false);

                $builder->whereHas('fund_provider_products', static function (Builder $builder) use ($product) {
                    $builder->where('product_id', $product->id);
                });
            });
        });
    }

    /**
     * @param Builder|Relation|Fund $query
     * @param Product $product
     * @return Builder|Relation|Fund
     */
    public static function whereProductsAreApprovedAndActiveFilter(
        Builder|Relation|Fund $query,
        Product $product,
    ): Builder|Relation|Fund {
        return self::whereProductsAreApprovedFilter(self::whereActiveFilter($query), $product);
    }

    /**
     * @param Builder|Relation|Fund $query
     * @param int|array $organization_id
     * @return Builder|Relation|Fund
     */
    public static function whereHasProviderFilter(
        Builder|Relation|Fund $query,
        int|array $organization_id,
    ): Builder|Relation|Fund {
        return $query->whereHas('providers.organization', static function (
            Builder $builder
        ) use ($organization_id) {
            $builder->whereIn('organizations.id', (array) $organization_id);
        });
    }

    /**
     * @param Builder|Relation|Fund $query
     * @param string $q
     * @return Builder|Relation|Fund
     */
    public static function whereQueryFilter(
        Builder|Relation|Fund $query,
        string $q,
    ): Builder|Relation|Fund {
        return $query->where(function (Builder $builder) use ($q) {
            $builder->where('name', 'LIKE', "%$q%");
            $builder->orWhere('description_text', 'LIKE', "%$q%");
            $builder->orWhere('description_short', 'LIKE', "%$q%");
            $builder->orWhereRelation('organization', 'organizations.name', 'LIKE', "%$q%");
        });
    }

    /**
     * @param Builder|Relation|Fund $query
     * @param array $states
     * @return Builder|Relation|Fund
     */
    public static function sortByState(
        Builder|Relation|Fund $query,
        array $states,
    ): Builder|Relation|Fund {
        foreach ($states as $state) {
            $query->orderByRaw('`funds`.`state` = ? DESC', [$state]);
        }

        return $query;
    }

    /**
     * @param Builder|Relation|Fund $query
     * @return Builder|Relation|Fund
     */
    public static function whereIsConfiguredByForus(
        Builder|Relation|Fund $query,
    ): Builder|Relation|Fund {
        return $query->whereHas('fund_config', static function (Builder $query) {
            return $query->where('is_configured', true);
        });
    }

    /**
     * @param Builder|Relation|Fund $query
     * @return Builder|Relation|Fund
     */
    public static function whereIsInternal(Builder|Relation|Fund $query): Builder|Relation|Fund
    {
        return $query->where('external', false);
    }

    /**
     * @param Builder|Relation|Fund $query
     * @return Builder|Relation|Fund
     */
    public static function whereExpired(Builder|Relation|Fund $query): Builder|Relation|Fund
    {
        return $query->where('end_date', '<', now()->format('Y-m-d'));
    }

    /**
     * @param Builder|Relation|Fund $query
     * @return Builder|Relation|Fund
     */
    public static function whereIsInternalConfiguredAndActive(
        Builder|Relation|Fund $query,
    ): Builder|Relation|Fund {
        return self::whereIsInternal(self::whereActiveFilter(self::whereIsConfiguredByForus($query)));
    }

    /**
     * @param Builder|Relation|Fund $query
     * @return Builder|Relation|Fund
     */
    public static function whereIsInternalAndConfigured(
        Builder|Relation|Fund $query,
    ): Builder|Relation|Fund {
        return self::whereIsInternal(self::whereIsConfiguredByForus($query));
    }

    /**
     * @param Builder|Relation|Fund $query
     * @param int|int[] $organizationId
     * @return Relation|Builder|Fund
     */
    public static function whereProviderProductsRequired(
        Builder|Relation|Fund $query,
        int | array $organizationId,
    ): Relation|Builder|Fund {
        return $query
            ->where('archived', false)
            ->whereRelation('fund_config', 'provider_products_required', true)
            ->whereHas('fund_providers', function (Builder $builder) use ($organizationId) {
                $builder->where('state', '!=', FundProvider::STATE_REJECTED);
                $builder->whereIn('organization_id', (array) $organizationId);
                $builder->doesntHave('organization.products');
            });
    }
}
