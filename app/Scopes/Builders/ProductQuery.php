<?php


namespace App\Scopes\Builders;

use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\FundProviderProduct;
use App\Models\Implementation;
use App\Models\Product;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Lang;

/**
 * Class ProductQuery
 * @package App\Scopes\Builders
 */
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
            self::whereFundNotExcluded($builder, $fund_id);

            $builder->where(static function(Builder $builder) use ($fund_id) {
                $builder->whereHas('fund_provider_products.fund_provider', static function(Builder $builder) use ($fund_id) {
                    $builder->whereIn('fund_id', (array) $fund_id);
                    $builder->where('state', FundProvider::STATE_ACCEPTED);
                    FundProviderQuery::whereApprovedForFundsFilter($builder, $fund_id);
                });

                $builder->orWhereHas('organization.fund_providers', static function(Builder $builder) use ($fund_id) {
                    $builder->where('state', FundProvider::STATE_ACCEPTED);
                    FundProviderQuery::whereApprovedForFundsFilter($builder, $fund_id);

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
     * @param Builder $builder
     * @param $fund_id
     * @return Builder
     */
    public static function whereFundNotExcluded(Builder $builder, $fund_id): Builder
    {
        $builder->where(function(Builder $builder) use ($fund_id) {
            $builder->whereNull('sponsor_organization_id');
            $builder->orWhereHas('sponsor_organization', function(Builder $builder) use ($fund_id) {
                $builder->whereHas('funds', function(Builder $builder) use ($fund_id) {
                    $builder->whereIn('id', (array) $fund_id);
                });
            });
        });

        $builder->where(function(Builder $builder) use ($fund_id) {
            foreach ((array) $fund_id as $fundId) {
                $builder->orWhere(function (Builder $builder) use ($fundId) {
                    $builder->whereHas('organization', static function (Builder $builder) use ($fundId) {
                        $builder->whereHas('fund_providers', static function (Builder $builder) use ($fundId) {
                            $builder->where('fund_id', $fundId);
                        });
                    });

                    $builder->whereDoesntHave('product_exclusions', static function (Builder $builder) use ($fundId) {
                        $builder->whereHas('fund_provider', static function (Builder $builder) use ($fundId) {
                            $builder->where('fund_id', $fundId);
                        });
                    });
                });
            }
        });

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
     * @param Builder $builder
     * @param Voucher $voucher
     * @param Builder|null $providerOrganization
     * @param bool $checkReservationFlags
     * @return Builder
     */
    public static function whereAvailableForVoucher(
        Builder $builder,
        Voucher $voucher,
        Builder $providerOrganization = null,
        bool $checkReservationFlags = true
    ): Builder {
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