<?php

namespace App\Scopes\Builders;

use App\Models\FundProductLimit;
use App\Models\FundProductLimitProduct;
use App\Models\FundProviderProduct;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ProductSubQuery
{
    /**
     * @param array $options
     * @param Builder|Relation|Product|null $builder
     * @throws Exception
     * @return Builder
     */
    public static function appendReservationStats(
        array $options,
        Builder|Relation|Product $builder = null,
    ): Builder {
        if (count(array_filter(Arr::only($options, ['identity_id', 'voucher_id', 'fund_id']))) == 0) {
            throw new Exception('At least one filter is required.');
        }

        $baseQuery = $builder->addSelect([
            'limit_multiplier' => self::limitMultiplierQuery($options),
            'limit_total' => self::limitTotalSubQuery($options),
            'limit_total_used' => self::limitTotalUsedSubQuery($options, true),
            'limit_per_identity' => self::limitPerIdentitySubQuery($options),
            'limit_per_identity_used' => self::limitTotalUsedSubQuery($options),
            'limit_by_fund_vouchers' => self::limitByFundVouchersQuery($options)
                ->selectRaw('COUNT(*)'),
            'limit_by_fund_vouchers_available' => self::limitByFundVouchersQuery($options)
                ->whereNotExists(self::limitReachedScopeOnlySelectedQuery())
                ->whereNotExists(self::limitReachedScopeAllExceptSelectedQuery())
                ->selectRaw('COUNT(*)'),
            'reservations_enabled' => Organization::query()
                ->whereColumn('id', 'organization_id')
                ->select('reservations_enabled'),
        ])->getQuery();

        $query = Product::query()->fromSub($baseQuery, 'products');

        $query->selectRaw('*,
            CAST(IF(
                ISNULL(`limit_total`),
                GREATEST(`limit_per_identity` - `limit_per_identity_used`, 0),
                GREATEST(`limit_total` - `limit_total_used`, 0)
            ) AS SIGNED) as `limit_total_available`,

            CAST(
                GREATEST(`limit_per_identity` - `limit_per_identity_used`, 0) AS SIGNED
            ) AS `limit_identity_available`,

            IF(
                `limit_by_fund_vouchers` > 0 AND `limit_by_fund_vouchers_available` = 0,
                0,
                CAST(GREATEST(
                    LEAST(
                        IF(
                            ISNULL(`limit_total`),
                            GREATEST(`limit_per_identity` - `limit_per_identity_used`, 0),
                            GREATEST(`limit_total` - `limit_total_used`, 0)
                        ),
                        GREATEST(`limit_per_identity` - `limit_per_identity_used`, 0)
                    ), 0
                ) AS SIGNED)
            ) as `limit_available`');

        return Product::query()->fromSub($query, 'products');
    }

    /**
     * @param array $options
     * @return Builder|Relation|Voucher
     */
    protected static function limitMultiplierQuery(array $options = []): Builder|Relation|Voucher
    {
        /** @var int|null $fund_id */
        $fund_id = Arr::get($options, 'fund_id');

        /** @var int|null $voucher_id */
        $voucher_id = Arr::get($options, 'voucher_id');

        /** @var string|null $identity_id */
        $identity_id = Arr::get($options, 'identity_id');

        return VoucherQuery::whereNotExpired(
            Voucher::query()->where(function (Builder $builder) use ($identity_id, $voucher_id) {
                $voucher_id && $builder->whereIn('vouchers.id', (array) $voucher_id);
                $identity_id && $builder->whereIn('vouchers.identity_id', (array) $identity_id);
            })->whereHas('fund', function (Builder $builder) use ($fund_id) {
                $fund_id && $builder->whereIn('funds.id', (array) $fund_id);
            })->whereNull('product_id')
                ->select([])->selectRaw('CAST(IFNULL(SUM(limit_multiplier), 1) as SIGNED)')
        );
    }

    /**
     * @param array $options
     * @return QBuilder
     */
    protected static function limitTotalSubQuery(array $options = []): QBuilder
    {
        $builder = self::queryFundProviderProduct($options)
            ->whereColumn('fund_provider_products.product_id', '=', 'products.id')
            ->select([])
            ->selectRaw('CAST(if(`limit_total_unlimited`, null, `limit_total`) as SIGNED) as `limit_total`')
            ->getQuery()
            ->whereNull('deleted_at');

        return DB::query()
            ->fromSub($builder, 'reservations')
            ->selectRaw('cast(sum(`limit_total`) as signed) as `limit_total`');
    }

    /**
     * @param array $options
     * @return QBuilder
     */
    protected static function limitPerIdentitySubQuery(array $options = []): QBuilder
    {
        $builder = self::queryFundProviderProduct($options)
            ->whereColumn('fund_provider_products.product_id', '=', 'products.id')
            ->select([])
            ->selectRaw('CAST(if(`limit_per_identity_unlimited`, null, `limit_per_identity` * `limit_multiplier`) AS SIGNED) as `limit`')
            ->getQuery()
            ->whereNull('deleted_at');

        return DB::query()
            ->fromSub($builder, 'reservations')
            ->selectRaw('cast(sum(`limit`) as signed) as `limit`');
    }

    /**
     * @param array $options
     * @return Builder|Relation|FundProviderProduct
     */
    protected static function queryFundProviderProduct(
        array $options = [],
    ): Builder|Relation|FundProviderProduct {
        /** @var int|null $fund_id */
        $fund_id = Arr::get($options, 'fund_id');

        /** @var int|null $voucher_id */
        $voucher_id = Arr::get($options, 'voucher_id');

        /** @var string|null $identity_id */
        $identity_id = Arr::get($options, 'identity_id');

        return FundProviderProduct::query()->where(function (Builder $builder) use ($fund_id, $voucher_id, $identity_id) {
            if ($fund_id || $voucher_id || $identity_id) {
                $builder->whereHas('fund_provider', function (Builder $builder) use ($fund_id, $voucher_id, $identity_id) {
                    $builder->whereHas('fund', function (Builder $builder) use ($fund_id, $voucher_id, $identity_id) {
                        $fund_id && $builder->whereIn('funds.id', (array) $fund_id);

                        if ($voucher_id || $identity_id) {
                            $builder->whereHas('vouchers', function (Builder $builder) use ($voucher_id, $identity_id) {
                                $voucher_id && $builder->whereIn('vouchers.id', (array) $voucher_id);
                                $identity_id && $builder->whereIn('vouchers.identity_id', (array) $identity_id);
                            });
                        }
                    });
                });
            }
        });
    }

    /**
     * @param array $options
     * @param bool $total
     * @return QBuilder
     */
    protected static function limitTotalUsedSubQuery(array $options = [], bool $total = false): QBuilder
    {
        if ($total) {
            $options['fund_voucher_id'] = $options['voucher_id'] ?? null;

            unset($options['identity_id']);
            unset($options['voucher_id']);
        }

        $builder = Product::fromSub(Product::query(), 'product_tmp')
            ->whereColumn('product_tmp.id', '=', 'products.id');

        $builder = $builder->selectSub(function (QBuilder $builder) use ($options) {
            $builder->fromSub(function (QBuilder $builder) use ($options) {
                // voucher transactions
                $builder->selectSub(VoucherTransaction::query()
                    ->select([])
                    ->selectRaw('count(*)')
                    ->where('state', '!=', VoucherTransaction::STATE_CANCELED)
                    ->whereColumn('product_id', '=', 'products.id')
                    ->where(function (Builder $builder) use ($options) {
                        if ($options['voucher_id'] ?? false) {
                            $builder->where(function (Builder|VoucherTransaction $builder) use ($options) {
                                $builder->whereIn('voucher_id', (array) $options['voucher_id']);
                                $builder->orWhereRelation('voucher.product_reservation', function (Builder $builder) use ($options) {
                                    $builder->whereIn('voucher_id', (array) $options['voucher_id']);
                                });
                            });
                        }

                        $builder->whereHas('voucher', function (Builder $builder) use ($options) {
                            static::voucherQuery($builder, $options);
                        });
                    }), 'count_transactions');

                // product vouchers without transactions
                $builder->selectSub(Voucher::query()
                    ->select([])
                    ->selectRaw('count(*)')
                    ->whereDoesntHave('product_reservation')
                    ->whereDoesntHave('transactions')
                    // product voucher
                    ->whereColumn('vouchers.product_id', '=', 'products.id')
                    ->where(function (Builder $builder) use ($options) {
                        if ($options['voucher_id'] ?? false) {
                            $builder->whereIn('parent_id', (array) $options['voucher_id']);
                        } else {
                            $builder->whereNull('parent_id');
                        }

                        static::voucherQuery($builder, $options);
                    }), 'count_vouchers');

                // reservations without transactions and state: pending, accepted
                $builder->selectSub(ProductReservation::query()
                    ->select([])
                    ->selectRaw('count(*)')
                    ->whereNotIn('state', array_merge([
                        ProductReservation::STATE_REJECTED,
                    ], ProductReservation::STATES_CANCELED))
                    ->whereDoesntHave('voucher_transaction')
                    ->whereColumn('product_id', '=', 'products.id')
                    ->where(function (Builder $builder) use ($options) {
                        if ($options['voucher_id'] ?? false) {
                            $builder->whereIn('voucher_id', (array) $options['voucher_id']);
                        }

                        $builder->whereHas('voucher', function (Builder $builder) use ($options) {
                            static::voucherQuery($builder, $options);
                        });
                    }), 'count_reservations');
            }, 'reservations');

            $builder->selectRaw('CAST((`count_transactions` + `count_vouchers` + `count_reservations`) as SIGNED) as `used_total`');
        }, 'count_reservations');

        return DB::query()
            ->fromSub($builder, 'reservations')
            ->selectRaw('cast(sum(count_reservations) as signed) as count_reservations');
    }

    /**
     * @param Builder|Relation|Voucher $builder
     * @param array $options
     * @return Builder|Relation|FundProviderProduct
     */
    protected static function voucherQuery(
        Builder|Relation|Voucher $builder,
        array $options,
    ): Builder|Relation|FundProviderProduct {
        VoucherQuery::whereNotExpired($builder);

        /** @var string|null $identity_id */
        if ($identity_id = Arr::get($options, 'identity_id')) {
            $builder->whereRelation('identity', 'id', $identity_id);
        }

        /** @var string|null $fund_id */
        if ($fund_id = Arr::get($options, 'fund_id')) {
            $builder->where('fund_id', $fund_id);
        }

        return $builder->whereHas('fund', function (Builder $builder) use ($options) {
            /** @var int|null $fund_voucher_id */
            if ($fund_voucher_id = Arr::get($options, 'fund_voucher_id')) {
                $builder->whereHas('vouchers', function (Builder $builder) use ($fund_voucher_id) {
                    $builder->where('vouchers.id', $fund_voucher_id);
                });
            }
        });
    }

    /**
     * @param array $options
     * @return Builder|Voucher
     */
    private static function limitByFundVouchersQuery(array $options): Builder|Voucher
    {
        $fundId = Arr::get($options, 'fund_id');
        $voucherId = Arr::get($options, 'voucher_id');
        $identityId = Arr::get($options, 'identity_id');

        if ($voucherId) {
            return VoucherQuery::whereNotExpiredAndActive(
                Voucher::query()->whereIn('vouchers.id', (array) $voucherId)
            );
        }

        if ($fundId && $identityId) {
            return VoucherQuery::whereNotExpiredAndActive(Voucher::query()
                ->whereIn('vouchers.fund_id', (array) $fundId)
                ->whereIn('vouchers.identity_id', (array) $identityId)
                ->whereNull('vouchers.product_id'));
        }

        return Voucher::query()->whereRaw('FALSE');
    }

    /**
     * @return Builder|FundProductLimit
     */
    private static function limitReachedScopeOnlySelectedQuery(): Builder|FundProductLimit
    {
        $reservedProductsCount = static::reservationsUniqueCountQuery()
            ->whereIn('product_reservations.product_id', static::fundProductLimitReservationsSubQuery());

        return static::activeFundProductLimitQuery(FundProductLimit::SCOPE_ONLY_SELECTED)
            ->whereHas('fund_products', fn (Builder $q) => $q->whereColumn(
                'fund_product_limit_products.product_id',
                'products.id'
            ))
            ->where($reservedProductsCount, '>=', DB::raw('fund_product_limits.limit'));
    }

    /**
     * @return Builder|FundProductLimit
     */
    private static function limitReachedScopeAllExceptSelectedQuery(): Builder|FundProductLimit
    {
        $reservedProductsCount = static::reservationsUniqueCountQuery()
            ->whereColumn('product_reservations.product_id', '!=', 'products.id')
            ->whereNotIn('product_reservations.product_id', static::fundProductLimitReservationsSubQuery());

        return static::activeFundProductLimitQuery(FundProductLimit::SCOPE_ALL_EXCEPT_SELECTED)
            ->whereDoesntHave('fund_products', fn (Builder $q) => $q->whereColumn(
                'fund_product_limit_products.product_id',
                'products.id'
            ))
            ->where($reservedProductsCount, '>=', DB::raw('fund_product_limits.limit'));
    }

    /**
     * @param string $scope
     * @return Builder|FundProductLimit
     */
    private static function activeFundProductLimitQuery(string $scope): Builder|FundProductLimit
    {
        return FundProductLimit::query()
            ->whereColumn('fund_product_limits.fund_id', 'vouchers.fund_id')
            ->where('fund_product_limits.state', FundProductLimit::STATE_ACTIVE)
            ->where('fund_product_limits.type', $scope);
    }

    /**
     * @return ProductReservation|Builder
     */
    private static function reservationsUniqueCountQuery(): Builder|ProductReservation
    {
        return ProductReservation::query()
            ->selectRaw('COUNT(DISTINCT product_reservations.product_id)')
            ->whereColumn('product_reservations.voucher_id', 'vouchers.id')
            ->whereNotIn('product_reservations.state', [
                ...ProductReservation::STATES_CANCELED,
                ProductReservation::STATE_REJECTED,
            ]);
    }

    /**
     * @return FundProductLimitProduct|Builder
     */
    private static function fundProductLimitReservationsSubQuery(): FundProductLimitProduct|Builder
    {
        return FundProductLimitProduct::query()
            ->select('product_id')
            ->whereColumn('fund_product_limit_id', 'fund_product_limits.id')
            ->whereColumn('product_id', '!=', 'products.id');
    }
}
