<?php


namespace App\Scopes\Builders;

use App\Models\Fund;
use App\Models\FundProviderProduct;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QBuilder;

/**
 * Class ProductQuery
 * @package App\Scopes\Builders
 */
class ProductSubQuery
{
    /**
     * @param array $options
     * @param Builder|null $builder
     * @return Builder
     */
    public static function appendReservationStats(array $options = [], Builder $builder = null): Builder
    {
        $totalsOptions = $options;

        unset($totalsOptions['identity_address']);
        unset($totalsOptions['voucher_id']);

        $builder ?: Product::query();

        $baseQuery = $builder->addSelect([
            'limit_multiplier' => self::limitMultiplierQuery($options),
            'limit_total' => self::limitTotalSubQuery($options),
            'limit_total_used' => self::limitTotalUsedSubQuery($totalsOptions),
            'limit_per_identity' => self::limitPerIdentitySubQuery($options),
            'limit_per_identity_used' => self::limitTotalUsedSubQuery($options),
            'reservations_subsidy_enabled' => Organization::whereColumn('id', 'organization_id')->select([
                'reservations_subsidy_enabled'
            ]),
            'reservations_budget_enabled' => Organization::whereColumn('id', 'organization_id')->select([
                'reservations_budget_enabled'
            ]),
        ])->getQuery();

        $query = Product::query()->fromSub($baseQuery, 'products');

        $query->selectRaw(implode('', array_map("trim", explode("\n",
            '*, 
            CAST(IF(
                ISNULL(`limit_total`),
                GREATEST(`limit_per_identity` - `limit_per_identity_used`, 0),
                GREATEST(`limit_total` - `limit_total_used`, 0)
            ) AS SIGNED) as `limit_total_available`,
            CAST(
                GREATEST(`limit_per_identity` - `limit_per_identity_used`, 0) AS SIGNED
            ) AS `limit_identity_available`,
            CAST(IF(reservations_subsidy_enabled, GREATEST(
                LEAST(
                    IF(
                        ISNULL(`limit_total`), 
                        GREATEST(`limit_per_identity` - `limit_per_identity_used`, 0), 
                        GREATEST(`limit_total` - `limit_total_used`, 0)
                    ),
                    GREATEST(`limit_per_identity` - `limit_per_identity_used`, 0)
                ), 0
            ), 0) AS SIGNED) as `limit_available`'))));

        return Product::query()->fromSub($query, 'products');
    }

    /**
     * @param array $options
     * @return Builder
     */
    public static function limitMultiplierQuery(array $options = []): Builder
    {
        /** @var int|null $fund_id */
        $fund_id = array_get($options, 'fund_id');

        /** @var int|null $voucher_id */
        $voucher_id = array_get($options, 'voucher_id');

        /** @var string|null $identity_address */
        $identity_address = array_get($options, 'identity_address');

        return VoucherQuery::whereNotExpiredAndActive(
            Voucher::query()->where(function(Builder $builder) use ($identity_address, $voucher_id) {
                $voucher_id && $builder->whereIn('vouchers.id', (array) $voucher_id);
                $identity_address && $builder->whereIn('vouchers.identity_address', (array) $identity_address);
            })->whereHas('fund', function(Builder $builder) use ($fund_id) {
                $builder->where('funds.type', Fund::TYPE_SUBSIDIES);
                $fund_id && $builder->whereIn('funds.id', (array) $fund_id);
            })->whereNull('product_id')
                ->select([])->selectRaw("CAST(IFNULL(SUM(limit_multiplier), 1) as SIGNED)")
        );
    }

    /**
     * @param array $options
     * @return Builder
     */
    protected static function limitTotalSubQuery(array $options = []): Builder
    {
        /** @var int|null $fund_id */
        $fund_id = array_get($options, 'fund_id');

        /** @var int|null $voucher_id */
        $voucher_id = array_get($options, 'voucher_id');

        return FundProviderProduct::query()->where(function(Builder $builder) use ($fund_id, $voucher_id) {
            $fund_id && $builder->whereHas('fund_provider', function(Builder $builder) use ($fund_id) {
                $builder->whereHas('fund', function(Builder $builder) use ($fund_id) {
                    $builder->whereIn('funds.id', (array) $fund_id);
                    $builder->where('funds.type', Fund::TYPE_SUBSIDIES);
                });
            });

            $voucher_id && $builder->whereHas('fund_provider', function(Builder $builder) use ($voucher_id) {
                $builder->whereHas('fund', function(Builder $builder) use ($voucher_id) {
                    $builder->whereHas('vouchers', function(Builder $builder) use ($voucher_id) {
                        $builder->whereIn('vouchers.id', (array) $voucher_id);
                    });
                });
            });
        })
            ->whereColumn('fund_provider_products.product_id', '=', 'products.id')
            ->select([])->selectRaw('CAST(if(`limit_total_unlimited`, null, `limit_total`) as SIGNED) as `limit_total`');
    }

    /**
     * @param array $options
     * @return Builder
     */
    public static function limitPerIdentitySubQuery(array $options = []): Builder
    {
        /** @var int|null $fund_id */
        $fund_id = array_get($options, 'fund_id');

        /** @var int|null $voucher_id */
        $voucher_id = array_get($options, 'voucher_id');

        return FundProviderProduct::query()->where(function(Builder $builder) use ($fund_id, $voucher_id) {
            $fund_id && $builder->whereHas('fund_provider', function(Builder $builder) use ($fund_id) {
                $builder->whereHas('fund', function(Builder $builder) use ($fund_id) {
                    $builder->whereIn('funds.id', (array) $fund_id);
                    $builder->where('funds.type', Fund::TYPE_SUBSIDIES);
                });
            });

            $voucher_id && $builder->whereHas('fund_provider', function(Builder $builder) use ($voucher_id) {
                $builder->whereHas('fund', function(Builder $builder) use ($voucher_id) {
                    $builder->whereHas('vouchers', function(Builder $builder) use ($voucher_id) {
                        $builder->whereIn('vouchers.id', (array) $voucher_id);
                    });
                });
            });
        })
            ->whereColumn('fund_provider_products.product_id', '=', 'products.id')
            ->select([])->selectRaw('CAST(`limit_per_identity` * `limit_multiplier` AS SIGNED) as `limit`');
    }

    /**
     * @param array $options
     * @return Builder
     */
    public static function limitTotalUsedSubQuery(array $options = []): Builder
    {
        /** @var int|null $fund_id */
        $fund_id = array_get($options, 'fund_id');

        /** @var int|null $voucher_id */
        $voucher_id = array_get($options, 'voucher_id');

        /** @var string|null $identity_address */
        $identity_address = array_get($options, 'identity_address');

        $builder =  FundProviderProduct::query()->where(function(Builder $builder) use ($fund_id) {
            $fund_id && $builder->whereHas('fund_provider', function(Builder $builder) use ($fund_id) {
                $builder->whereIn('fund_providers.fund_id', (array) $fund_id);
            });
        })->whereColumn('fund_provider_products.product_id', '=', 'products.id')->select([]);

        $builder->selectSub(function(QBuilder $builder) use ($voucher_id, $identity_address) {
            $builder->fromSub(function(QBuilder $builder) use ($voucher_id, $identity_address) {
                $builder->selectSub(VoucherTransaction::query()
                    ->select([])
                    ->selectRaw('count(*)')
                    ->where('state', '!=', VoucherTransaction::STATE_CANCELED)
                    ->whereColumn('product_id', '=', 'products.id')
                    ->where(function(Builder $builder) use ($voucher_id, $identity_address) {
                        $voucher_id && $builder->whereIn('voucher_id', (array) $voucher_id);

                        if ($identity_address) {
                            $builder->whereHas('voucher', function(Builder $builder) use ($identity_address) {
                                $builder->where(compact('identity_address'));
                            });
                        }
                    }), 'count_transactions');

                $builder->selectSub(Voucher::query()
                    ->select([])
                    ->selectRaw('count(*)')
                    ->whereDoesntHave('product_reservation')
                    ->whereDoesntHave('transactions')
                    ->whereColumn('vouchers.product_id', '=', 'products.id')
                    ->where(function(Builder $builder) use ($voucher_id, $identity_address) {
                        if ($voucher_id) {
                            $builder->whereIn('parent_id', (array) $voucher_id);
                        } else {
                            $builder->whereNotNull('parent_id');
                        }

                        if ($identity_address) {
                            $builder->whereHas('parent', function(Builder $builder) use ($identity_address) {
                                $builder->where(compact('identity_address'));
                            });
                        } else {
                            $builder->whereNotNull('parent_id');
                        }
                    }), 'count_vouchers');

                $builder->selectSub(ProductReservation::query()
                    ->select([])
                    ->selectRaw('count(*)')
                    ->whereNotIn('state', [
                        ProductReservation::STATE_CANCELED,
                        ProductReservation::STATE_REJECTED,
                    ])
                    ->whereDoesntHave('voucher_transaction')
                    ->whereColumn('product_id', '=', 'products.id')
                    ->where(function(Builder $builder) use ($voucher_id, $identity_address) {
                        $voucher_id && $builder->whereIn('voucher_id', (array) $voucher_id);

                        if ($identity_address) {
                            $builder->whereHas('voucher', function(Builder $builder) use ($identity_address) {
                                $builder->where(compact('identity_address'));
                            });
                        }
                    }), 'count_reservations');
            }, 'reservations');

            $builder->selectRaw("CAST((`count_transactions` + `count_vouchers` + `count_reservations`) as SIGNED) as `used_total`");
        }, 'count_reservations');

        return $builder;
    }
}