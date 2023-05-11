<?php


namespace App\Scopes\Builders;

use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\FundProviderProduct;
use App\Models\FundProviderProductExclusion;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Lang;

class ProductQuery
{
    /**
     * @param Builder|Product $query
     * @param array|int $fund_id
     * @return Builder|Product
     */
    public static function approvedForFundsFilter(Builder|Product $query, array|int $fund_id): Builder|Product
    {
        return $query->where(static function(Builder $builder) use ($fund_id) {
            self::whereFundNotExcluded($builder, $fund_id);

            $builder->where(static function(Builder $builder) use ($fund_id) {
                $builder->whereHas('fund_provider_products.fund_provider', static function(Builder $builder) use ($fund_id) {
                    $builder->whereIn('fund_id', (array) $fund_id);
                    $builder->where('state', FundProvider::STATE_ACCEPTED);
                    FundProviderQuery::whereApprovedForFundsFilter($builder, $fund_id);
                });

                $builder->orWhereHas('organization.fund_providers', static function(Builder $builder) use ($fund_id) {
                    $builder->whereIn('fund_id', (array) $fund_id);
                    $builder->where('state', FundProvider::STATE_ACCEPTED);
                    FundProviderQuery::whereApprovedForFundsFilter($builder, $fund_id);

                    $builder->whereRelation('fund', 'type', Fund::TYPE_BUDGET);
                    $builder->where('allow_products', true);
                });
            });
        });
    }

    /**
     * @param Builder|Relation|Product $query
     * @param array|int $fund_id
     * @return Builder|Relation|Product
     */
    public static function notApprovedForFundsFilter(
        Builder|Relation|Product $query,
        array|int $fund_id
    ): Builder|Relation|Product {
        return $query->whereNotIn('id', self::approvedForFundsFilter(clone $query, $fund_id)->select('id'));
    }

    /**
     * @param Builder|Relation|Product $builder
     * @param $fund_id
     * @return Builder|Relation|Product
     */
    public static function whereFundNotExcluded(
        Builder|Relation|Product $builder, $fund_id
    ): Builder|Relation|Product {
        $builder->where(function(Builder $builder) use ($fund_id) {
            $builder->whereNull('sponsor_organization_id');
            $builder->orWhereHas('sponsor_organization', function(Builder|Organization $builder) use ($fund_id) {
                $builder->whereHas('funds', function (Builder|Fund $builder) use ($fund_id) {
                    $builder->whereIn('id', (array) $fund_id);
                });
            });
        });

        $builder->whereHas('product_exclusions', function (Builder|FundProviderProductExclusion $builder) use ($fund_id) {
            $builder->whereHas('fund_provider', function (Builder|FundProvider $builder) use ($fund_id) {
                $builder->whereIn('fund_id', (array) $fund_id);
            });
        }, '<', count((array) $fund_id));

        return $builder;
    }

    /**
     * @param Builder $query
     * @param $fund_id
     * @return Builder
     */
    public static function whereHasFundApprovalHistory(Builder $query, $fund_id): Builder
    {
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
    public static function whereFundNotExcludedOrHasHistory(Builder $query, $fund_id): Builder
    {
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
     * @param $productCategoryId
     * @param bool $withChildes
     * @return Builder
     */
    public static function productCategoriesFilter(
        Builder $query,
        $productCategoryId,
        bool $withChildes = true
    ): Builder {
        $query->whereHas('product_category', function(Builder $builder) use ($productCategoryId, $withChildes) {
            $categoriesQuery = $builder->whereIn('id', (array) $productCategoryId);

            if ($withChildes) {
                $categoriesQuery->orWhereHas('ancestors', function(Builder $builder) use ($productCategoryId) {
                    $builder->whereIn('id', (array) $productCategoryId);
                });
            }
        });

        return $query;
    }

    /**
     * @param Builder $query
     * @param string $q
     * @return Builder
     */
    public static function queryFilter(Builder $query, string $q = ''): Builder
    {
        return $query->where(static function (Builder $query) use ($q) {
            $query->where('products.name', 'LIKE', "%$q%");
            $query->orWhere('products.description', 'LIKE', "%$q%");
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
            $query->where('products.name', 'LIKE', "%$q%");
            $query->orWhere('products.description_text', 'LIKE', "%$q%");

            $query->orWhereHas('organization', static function(Builder $builder) use ($q) {
                $builder->where('organizations.name', 'LIKE', "%$q%");
                $builder->orWhere('organizations.description_text', 'LIKE', "%$q%");
            });

            if (strlen($q) >= 3) {
                $query->orWhereHas('product_category.translations', static function(Builder $builder) use ($q) {
                    $builder->where('name', 'LIKE', "%$q%");
                    $builder->where('locale', Lang::locale());
                });
            }
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
            self::whereNotExpired($builder->where('sold_out', false));
        });
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public static function whereNotExpired(Builder $query): Builder
    {
        return $query->where(static function(Builder $builder) {
            $builder->whereNull('products.expire_at');
            $builder->orWhere('products.expire_at', '>=', today());
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

    /**
     * Add min_price column form the action funds
     * Has to be used as the last query builder operation (unless you have reasons not to)
     *
     * @param Builder $builder
     * @return Builder
     */
    public static function addPriceMinAndMaxColumn(Builder $builder): Builder
    {
        $fundProviderProductQuery = function(string $type) {
            return FundProviderProduct::whereHas('fund_provider.fund', function(Builder $builder) {
                $builder->where('funds.type', Fund::TYPE_SUBSIDIES);
                $builder->whereIn('funds.id', Implementation::activeFundsQuery()->pluck('id'));
            })->select('amount')
                ->orderBy('amount', $type)
                ->whereColumn('fund_provider_products.product_id', 'products.id')
                ->limit(1);
        };

        $builder->addSelect([
            'sponsor_amount_min' => $fundProviderProductQuery('asc'),
            'sponsor_amount_max' => $fundProviderProductQuery('desc'),
        ]);

        /** @var Builder $query */
        $query = Product::fromSub($builder->getQuery(), 'products');

        $query->selectRaw(
            '*, ' .
            '(CASE WHEN `sponsor_amount_max` IS NULL THEN `price` ELSE ' .
            '(CASE WHEN `price` - `sponsor_amount_max` < 0 THEN 0 ELSE ' .
            '`price` - `sponsor_amount_max` END) END) as `price_min`, ' .

            '(CASE WHEN `sponsor_amount_min` IS NULL THEN `price` ELSE ' .
            '(CASE WHEN `price` - `sponsor_amount_min` < 0 THEN 0 ELSE ' .
            '`price` - `sponsor_amount_min` END) END) as `price_max`'
        );

        return $query;
    }

    /**
     * @param Builder|Product $builder
     * @param Voucher $voucher
     * @param Builder|null $providerOrganization
     * @param bool $checkReservationFlags
     * @return Builder
     */
    public static function whereAvailableForVoucher(
        Builder|Product $builder,
        Voucher $voucher,
        Builder $providerOrganization = null,
        bool $checkReservationFlags = true
    ): Builder {
        $builder->where(function(Builder $builder) use ($voucher) {
            $builder->whereHas('fund_provider_products', function (Builder $builder) use ($voucher) {
                FundProviderProductQuery::whereInLimitsFilter($builder, $voucher);
            });

            if ($voucher->isBudgetType()) {
                $builder->orWhereDoesntHave('fund_provider_products', function (Builder $builder) use ($voucher) {
                    $builder->whereRelation('fund_provider', 'fund_id', $voucher->fund_id);
                });
            }
        });

        $builder = $builder->where('price', '<=', $voucher->amount_available);
        $builder = ProductQuery::approvedForFundsAndActiveFilter($builder, $voucher->fund_id);

        if ($voucher->product_id) {
            $builder->where('id', $voucher->product_id);
        }

        if ($providerOrganization) {
            $builder->whereIn('organization_id', $providerOrganization);
        }

        if ($checkReservationFlags) {
            self::whereReservationEnabled($builder, $voucher->fund->isTypeSubsidy() ? 'subsidy' : 'budget');

            if (!$voucher->fund->fund_config->allow_reservations) {
                $builder->whereIn('id', []);
            }
        }

        return $builder;
    }

    /**
     * @param Builder $builder
     * @param string $type
     * @return Builder
     */
    public static function whereReservationEnabled(Builder $builder, string $type = 'subsidy'): Builder
    {
        return $builder->whereHas('organization', function(Builder $builder) use ($type) {
            if ($type === 'subsidy') {
                $builder->where('reservations_subsidy_enabled', true);
            }

            if ($type === 'budget') {
                $builder->where('reservations_budget_enabled', true);
            }
        });
    }
}