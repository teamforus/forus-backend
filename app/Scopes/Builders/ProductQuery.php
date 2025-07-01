<?php

namespace App\Scopes\Builders;

use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\FundProviderProduct;
use App\Models\FundProviderProductExclusion;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Lang;

class ProductQuery
{
    /**
     * @param Builder|Relation|Product $query
     * @param array|int $fund_id
     * @return Builder|Relation|Product
     */
    public static function approvedForFundsFilter(
        Builder|Relation|Product $query,
        array|int $fund_id,
    ): Builder|Relation|Product {
        return $query->where(static function (Builder $builder) use ($fund_id) {
            self::whereFundNotExcluded($builder, $fund_id);

            $builder->where(static function (Builder $builder) use ($fund_id) {
                $builder->whereHas('fund_provider_products.fund_provider', static function (Builder $builder) use ($fund_id) {
                    $builder->whereIn('fund_id', (array) $fund_id);
                    $builder->where('state', FundProvider::STATE_ACCEPTED);
                    FundProviderQuery::whereApprovedForFundsFilter($builder, $fund_id);
                    $builder->where('excluded', false);
                });

                $builder->orWhereHas('organization.fund_providers', static function (Builder $builder) use ($fund_id) {
                    $builder->whereIn('fund_id', (array) $fund_id);
                    $builder->where('state', FundProvider::STATE_ACCEPTED);
                    FundProviderQuery::whereApprovedForFundsFilter($builder, $fund_id);

                    $builder->where('allow_products', true);
                    $builder->where('excluded', false);
                });
            });
        });
    }

    /**
     * @param Builder|Relation|Product $query
     * @param Builder|array|int $fund_id
     * @return Builder|Relation|Product
     */
    public static function hasPendingOrAcceptedProviderForFund(
        Builder|Relation|Product $query,
        Builder|array|int $fund_id,
    ): Builder|Relation|Product {
        return $query->whereHas('organization.fund_providers', static function (Builder $builder) use ($fund_id) {
            $builder->whereIn('fund_id', is_numeric($fund_id) ? [$fund_id] : $fund_id);
            $builder->whereIn('state', [
                FundProvider::STATE_PENDING,
                FundProvider::STATE_ACCEPTED,
            ]);
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
     * @param int|array $fund_id
     * @return Builder|Relation|Product
     */
    public static function whereFundNotExcluded(
        Builder|Relation|Product $builder,
        int|array $fund_id,
    ): Builder|Relation|Product {
        $relevantFundsQuery = Fund::query()->whereHas('fund_providers', function (Builder $builder) use ($fund_id) {
            $builder->whereColumn('fund_providers.organization_id', 'products.organization_id');
            $builder->whereIn('fund_providers.fund_id', (array) $fund_id);
        })
            ->whereIn('id', (array) $fund_id);

        $builder->where(function (Builder $builder) use ($fund_id) {
            $builder->whereNull('sponsor_organization_id');
            $builder->orWhereHas('sponsor_organization', function (Builder|Organization $builder) use ($fund_id) {
                $builder->whereHas('funds', function (Builder|Fund $builder) use ($fund_id) {
                    $builder->whereIn('id', (array) $fund_id);
                });
            });
        });

        $builder->whereHas('product_exclusions', function (Builder|FundProviderProductExclusion $builder) use ($fund_id) {
            $builder->whereHas('fund_provider', function (Builder|FundProvider $builder) use ($fund_id) {
                $builder->whereIn('fund_id', (array) $fund_id);
            });
        }, '<', $relevantFundsQuery->selectRaw('count(*)'));

        return $builder;
    }

    /**
     * @param Builder|Relation|Product $query
     * @param int|array $fund_id
     * @return Builder|Relation|Product
     */
    public static function whereHasFundApprovalHistory(
        Builder|Relation|Product $query,
        int|array $fund_id,
    ): Builder|Relation|Product {
        return $query->whereHas('fund_provider_products', function (Builder $builder) use ($fund_id) {
            TrashedQuery::withTrashed($builder);

            $builder->whereHas('fund_provider', static function (Builder $builder) use ($fund_id) {
                $builder->whereIn('fund_id', (array) $fund_id);
            });
        });
    }

    /**
     * @param Builder|Relation|Product $query
     * @param int|array $fund_id
     * @return Builder|Relation|Product
     */
    public static function whereFundNotExcludedOrHasHistory(
        Builder|Relation|Product $query,
        int|array $fund_id,
    ): Builder|Relation|Product {
        return $query->where(static function (Builder $builder) use ($fund_id) {
            $builder->where(static function (Builder $builder) use ($fund_id) {
                self::whereFundNotExcluded($builder, $fund_id);
            });

            $builder->orWhere(static function (Builder $builder) use ($fund_id) {
                self::whereHasFundApprovalHistory($builder, $fund_id);
            });
        });
    }

    /**
     * @param Builder|Relation|Product $query
     * @param int|array $productCategoryId
     * @param bool $withChildes
     * @return Builder|Relation|Product
     */
    public static function productCategoriesFilter(
        Builder|Relation|Product $query,
        int|array $productCategoryId,
        bool $withChildes = true,
    ): Builder|Relation|Product {
        $query->whereHas('product_category', function (Builder $builder) use ($productCategoryId, $withChildes) {
            $categoriesQuery = $builder->whereIn('id', (array) $productCategoryId);

            if ($withChildes) {
                $categoriesQuery->orWhereHas('ancestors', function (Builder $builder) use ($productCategoryId) {
                    $builder->whereIn('id', (array) $productCategoryId);
                });
            }
        });

        return $query;
    }

    /**
     * @param Builder|Relation|Product $query
     * @param string $q
     * @return Builder|Relation|Product
     */
    public static function queryFilter(
        Builder|Relation|Product $query,
        string $q = '',
    ): Builder|Relation|Product {
        return $query->where(static function (Builder $query) use ($q) {
            $query->where('products.name', 'LIKE', "%$q%");
            $query->orWhere('products.description', 'LIKE', "%$q%");
        });
    }

    /**
     * @param Builder|Relation|Product $query
     * @param string $q
     * @return Builder|Relation|Product
     */
    public static function queryDeepFilter(
        Builder|Relation|Product $query,
        string $q = '',
    ): Builder|Relation|Product {
        return $query->where(static function (Builder $query) use ($q) {
            $query->where('products.name', 'LIKE', "%$q%");
            $query->orWhere('products.description_text', 'LIKE', "%$q%");

            $query->orWhereHas('organization', static function (Builder $builder) use ($q) {
                $builder->where('organizations.name', 'LIKE', "%$q%");
                $builder->orWhere('organizations.description_text', 'LIKE', "%$q%");
            });

            if (strlen($q) >= 3) {
                $query->orWhereHas('product_category.translations', static function (Builder $builder) use ($q) {
                    $builder->where('name', 'LIKE', "%$q%");
                    $builder->where('locale', Lang::locale());
                });
            }
        });
    }

    /**
     * @param Builder|Relation|Product $query
     * @param bool $unlimited_stock
     * @return Builder|Relation|Product
     */
    public static function unlimitedStockFilter(
        Builder|Relation|Product $query,
        bool $unlimited_stock,
    ): Builder|Relation|Product {
        return $query->where('unlimited_stock', $unlimited_stock);
    }

    /**
     * @param Builder|Relation|Product $query
     * @return Builder|Relation|Product
     */
    public static function inStockAndActiveFilter(
        Builder|Relation|Product $query
    ): Builder|Relation|Product {
        return $query->where(static function (Builder $builder) {
            self::whereNotExpired($builder->where('sold_out', false));
        });
    }

    /**
     * @param Builder|Relation|Product $query
     * @return Builder|Relation|Product
     */
    public static function whereNotExpired(
        Builder|Relation|Product $query,
    ): Builder|Relation|Product {
        return $query->where(static function (Builder $builder) {
            $builder->whereNull('products.expire_at');
            $builder->orWhere('products.expire_at', '>=', today());
        });
    }

    /**
     * @param Builder|Relation|Product $query
     * @param int|array $fund_id
     * @return Builder|Relation|Product
     */
    public static function approvedForFundsAndActiveFilter(
        Builder|Relation|Product $query,
        int|array $fund_id,
    ): Builder|Relation|Product {
        return self::approvedForFundsFilter(self::inStockAndActiveFilter($query), $fund_id);
    }

    /**
     * Add min_price column form the action funds
     * Has to be used as the last query builder operation (unless you have reasons not to).
     *
     * @param Builder|Relation|Product $builder
     * @return Builder|Relation|Product
     */
    public static function addPriceMinAndMaxColumn(
        Builder|Relation|Product $builder,
    ): Builder|Relation|Product {
        $fundProviderProductQuery = function (string $type) {
            return FundProviderProduct::whereHas('fund_provider.fund', function (Builder $builder) {
                $builder->where('fund_provider_products.payment_type', FundProviderProduct::PAYMENT_TYPE_SUBSIDY);
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
     * @param Builder|Relation|Product $builder
     * @param Voucher $voucher
     * @param int|array|Builder|null $organization_id
     * @param bool $checkReservationFlags
     * @param bool $validateLimits
     * @return Builder
     */
    public static function whereAvailableForVoucher(
        Builder|Relation|Product $builder,
        Voucher $voucher,
        null|int|array|Builder $organization_id = null,
        bool $checkReservationFlags = true,
        bool $validateLimits = true,
    ): Builder {
        $builder->where(function (Builder $builder) use ($voucher, $validateLimits, $organization_id) {
            $builder->where(function (Builder|Product $builder) use ($voucher) {
                $builder->whereDoesntHave('fund_provider_products', function (Builder $builder) use ($voucher) {
                    $builder->whereRelation('fund_provider', 'fund_id', $voucher->fund_id);
                });

                $builder->where('price', '<=', $voucher->amount_available);
            });

            $builder->orWhereHas('fund_provider_products', function (
                Builder|Product $builder,
            ) use ($voucher, $validateLimits, $organization_id) {
                FundProviderProductQuery::whereAvailableForVoucher($builder, $voucher, $organization_id, $validateLimits);
            });
        });

        $builder = ProductQuery::approvedForFundsAndActiveFilter($builder, $voucher->fund_id);

        if ($voucher->product_id) {
            $builder->where('id', $voucher->product_id);
        }

        if ($organization_id) {
            if (is_numeric($organization_id) || is_array($organization_id)) {
                $builder->whereIn('organization_id', (array) $organization_id);
            }

            if ($organization_id instanceof Builder) {
                $builder->whereIn('organization_id', $organization_id);
            }
        }

        if ($checkReservationFlags) {
            self::whereReservationEnabled($builder);

            if (!$voucher->fund->fund_config->allow_reservations) {
                $builder->whereIn('id', []);
            }
        }

        return $builder;
    }

    /**
     * @param Builder|Relation|Product $builder
     * @return Builder|Relation|Product
     */
    public static function whereReservationEnabled(
        Builder|Relation|Product $builder,
    ): Builder|Relation|Product {
        $builder->where(function (Builder $builder) {
            $builder->where('reservation_enabled', true);

            $builder->whereHas('organization', function (Builder $builder) {
                $builder->where('reservations_enabled', true);
            });
        });

        return $builder;
    }

    /**
     * @param Builder|Relation|Product $query
     * @return Builder|Relation|Product
     */
    public static function stockAmountSubQuery(
        Builder|Relation|Product $query,
    ): Builder|Relation|Product {
        $query->addSelect([
            'reservations_count' => ProductReservation::whereIn('state', [
                ProductReservation::STATE_WAITING,
                ProductReservation::STATE_PENDING,
            ])->where('product_id', 'products.id')->selectRaw('COUNT(*)'),
            'transactions_count' => VoucherTransaction::where([
                'product_id' => 'products.id',
            ])->selectRaw('COUNT(*)'),
        ])->getQuery();

        return Product::query()->fromSub(Product::fromSub($query, 'products')->selectRaw(
            '*, IF(`unlimited_stock`, NULL, `total_amount` - (`reservations_count` + `transactions_count`)) as `stock_amount`'
        ), 'products');
    }

    /**
     * @param Builder|Relation|Product $query
     * @return Builder|Relation|Product
     */
    public static function addSelectLastMonitoredChangedDate(
        Builder|Relation|Product $query
    ): Builder|Relation|Product {
        $subQuery = EventLog::where([
            'loggable_type' => 'product',
            'event' => Product::EVENT_MONITORED_FIELDS_UPDATED,
        ])->whereColumn([
            'loggable_id' => 'products.id',
        ])->select('event_logs.created_at')->limit(1);

        return $query->addSelect([
            'last_monitored_change_at' => $subQuery,
        ]);
    }
}
