<?php


namespace App\Scopes\Builders;

use App\Models\Fund;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Builder;

class ProductQuery
{
    /**
     * @param Builder $query
     * @param int|array $fund_id
     * @return Builder
     */
    public static function approvedForFundsFilter(Builder $query, $fund_id): Builder
    {
        return $query->where(static function(Builder $builder) use ($fund_id) {
            $builder = self::whereFundNotExcluded($builder, $fund_id);

            $builder->where(static function(Builder $builder) use ($fund_id) {
                $builder->whereHas('fund_provider_products.fund_provider', static function(
                    Builder $builder
                ) use ($fund_id) {
                    $builder->whereIn('fund_id', (array) $fund_id);
                });

                $builder->orWhereHas('organization.fund_providers', static function(
                    Builder $builder
                ) use ($fund_id) {
                    $builder->where([
                        'allow_products' => TRUE,
                    ])->whereIn('fund_id', (array) $fund_id);

                    $builder->whereHas('fund', function(Builder $builder) {
                        $builder->where('type', '=', Fund::TYPE_BUDGET);
                    });
                });
            });
        });
    }

    /**
     * @param Builder $query
     * @param $fund_id
     * @return Builder
     */
    public static function whereFundNotExcluded(
        Builder $query, $fund_id
    ): Builder {
        return $query->whereHas('product_exclusions', static function(Builder $builder) use ($fund_id) {
            $builder->whereHas('fund_provider', function(Builder $builder) use ($fund_id) {
                $builder->whereIn('fund_id', (array) $fund_id);
            });
        }, '<', count((array) $fund_id));
    }

    /**
     * @param Builder $query
     * @param $fund_id
     * @return Builder
     */
    public static function whereHasFundApprovalHistory(
        Builder $query, $fund_id
    ): Builder {
        return $query->whereHas('fund_provider_products', function(Builder $builder) use ($fund_id) {
            TrashedQuery::withTrashed($builder);

            $builder->whereHas('fund_provider', static function(Builder $builder) use ($fund_id) {
                $builder->whereIn('fund_id', (array) $fund_id);
            });
        });
    }

    /**
     * @param Builder $query
     * @param $fund_id
     * @return Builder
     */
    public static function whereFundNotExcludedOrHasHistory(
        Builder $query, $fund_id
    ): Builder {
        return $query->where(static function(Builder $builder) use ($fund_id) {
            $builder->where(static function(Builder $builder) use ($fund_id) {
                self::whereFundNotExcluded($builder, $fund_id);
            });

            $builder->orWhere(static function(Builder $builder) use ($fund_id) {
                self::whereHasFundApprovalHistory($builder, $fund_id);
            });
        });
    }

    /**
     * @param Builder $query
     * @param int|array $product_category_id
     * @param bool $andSubcategories
     * @return Builder
     */
    public static function productCategoriesFilter(
        Builder $query,
        $product_category_id,
        bool $andSubcategories = true
    ): Builder {
        $productCategories = [];

        if (is_numeric($product_category_id) && $andSubcategories) {
            $productCategories = ProductCategory::descendantsAndSelf(
                $product_category_id
            )->pluck('id')->toArray();
        } elseif (is_array($product_category_id) && $andSubcategories) {
            foreach ($product_category_id as $_product_category_id) {
                foreach (ProductCategory::descendantsAndSelf(
                    $_product_category_id
                )->pluck('id') as $id) {
                    $productCategories[] = $id;
                }
            }
        }

        return $query->whereIn('product_category_id', $productCategories);
    }

    /**
     * @param Builder $query
     * @param string $q
     * @return Builder
     */
    public static function queryFilter(Builder $query, string $q = ''): Builder
    {
        return $query->where(static function (Builder $query) use ($q) {
            $query->where('name', 'LIKE', "%{$q}%");
            $query->orWhere('description', 'LIKE', "%{$q}%");
        });
    }

    /**
     * @param Builder $query
     * @param string $q
     * @return Builder
     */
    public static function queryDeepFilter(Builder $query, string $q = ''): Builder
    {
        return $query->where(static function (Builder $query) use ($q) {
            $query->where('name', 'LIKE', "%{$q}%");
            $query->orWhere('description', 'LIKE', "%{$q}%");
            $query->orWhereHas('organization', static function(Builder $builder) use ($q) {
                $builder->where('name', 'LIKE', "%{$q}%");
            });
        });
    }

    /**
     * @param Builder $query
     * @param bool $unlimited_stock
     * @return Builder
     */
    public static function unlimitedStockFilter(Builder $query, bool $unlimited_stock): Builder
    {
        return $query->where('unlimited_stock', $unlimited_stock);
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public static function inStockAndActiveFilter(Builder $query): Builder
    {
        return $query->where(static function(Builder $builder) {
            return $builder
                ->where('sold_out', false)
                ->where(static function(Builder $builder) {
                    $builder->whereNull('expire_at');
                    $builder->orWhere('expire_at', '>', date('Y-m-d'));
                });
        });
    }

    /**
     * @param Builder $query
     * @param $fund_id
     * @return Builder
     */
    public static function approvedForFundsAndActiveFilter(Builder $query, $fund_id): Builder
    {
        return self::approvedForFundsFilter(self::inStockAndActiveFilter($query), $fund_id);
    }
}