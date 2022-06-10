<?php


namespace App\Scopes\Builders;

use App\Models\ProductReservation;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QBuilder;
use Illuminate\Support\Arr;

/**
 * Class VoucherQuery
 * @package App\Scopes\Builders
 */
class VoucherQuery
{
    /**
     * @param Builder $builder
     * @param string $identity_address
     * @param $fund_id
     * @param null $organization_id Provider organization id
     * @return Builder
     */
    public static function whereProductVouchersCanBeScannedForFundBy(
        Builder $builder,
        string $identity_address,
        $fund_id,
        $organization_id = null
    ): Builder {
        $builder = $builder->whereHas('product', static function(Builder $builder) use (
            $fund_id, $identity_address, $organization_id
        ) {
            $builder->whereHas('organization', static function(Builder $builder) use (
                $fund_id, $identity_address, $organization_id
            ) {
                if ($organization_id instanceof Builder) {
                    $builder->whereIn('organizations.id', $organization_id);
                } else {
                    $builder->whereIn('organizations.id', (array) $organization_id);
                }

                OrganizationQuery::whereHasPermissions($builder, $identity_address, 'scan_vouchers');

                $builder->whereHas('fund_providers', static function(Builder $builder) use ($fund_id) {
                    FundProviderQuery::whereApprovedForFundsFilter($builder, $fund_id, 'product');
                });
            });

            ProductQuery::approvedForFundsFilter($builder, $fund_id);
        });

        return $builder->where(function(Builder $builder) {
            $builder->whereDoesntHave('transactions');
            $builder->whereDoesntHave('product_reservation', function(Builder $builder) {
                $builder->where('state', '!=', ProductReservation::STATE_PENDING);
                $builder->orWhereDate('expire_at', '<', now());
            });
        });
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    public static function whereNotExpired(Builder $builder): Builder
    {
        return $builder->where(static function(Builder $builder) {
            $builder->where('vouchers.expire_at', '>=', today());

            $builder->whereHas('fund', static function(Builder $builder) {
                $builder->whereDate('end_date', '>=', today());
            });
        });
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    public static function whereActive(Builder $builder): Builder
    {
        return $builder->where('state', Voucher::STATE_ACTIVE);
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    public static function wherePending(Builder $builder): Builder
    {
        return $builder->where('state', Voucher::STATE_PENDING);
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    public static function whereDeactivated(Builder $builder): Builder
    {
        return $builder->where('state', Voucher::STATE_DEACTIVATED);
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    public static function whereNotExpiredAndActive(Builder $builder): Builder
    {
        return self::whereNotExpired(self::whereActive($builder));
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    public static function whereNotExpiredAndPending(Builder $builder): Builder
    {
        return self::whereNotExpired(self::wherePending($builder));
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    public static function whereNotExpiredAndDeactivated(Builder $builder): Builder
    {
        return self::whereNotExpired(self::whereDeactivated($builder));
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    public static function whereExpired(Builder $builder): Builder
    {
        return $builder->where(static function(Builder $builder) {
            $builder->where('vouchers.expire_at', '<', today());

            $builder->orWhereHas('fund', static function(Builder $builder) {
                $builder->where('end_date', '<', today());
            });
        });
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    public static function whereExpiredButActive(Builder $builder): Builder
    {
        return static::whereActive(static::whereExpired($builder));
    }

    /**
     * @param Builder $builder
     * @param string $query
     * @return Builder
     */
    public static function whereSearchSponsorQuery(Builder $builder, string $query): Builder
    {
        return $builder->where(static function (Builder $builder) use ($query) {
            $builder->where('note', 'LIKE', "%$query%");
            $builder->orWhere('activation_code', 'LIKE', "%$query%");
            $builder->orWhere('activation_code_uid', 'LIKE', "%$query%");

            if ($email_identities = identity_repo()->identityAddressesByEmailSearch($query)) {
                $builder->orWhereIn('identity_address', $email_identities);
            }

            if ($bsn_identities = record_repo()->identityAddressByBsnSearch($query)) {
                $builder->orWhereIn('identity_address', $bsn_identities);
            }

            $builder->orWhereHas('voucher_relation', function (Builder $builder) use ($query) {
                return $builder->where('bsn', 'LIKE', "%$query%");
            });

            $builder->orWhereHas('physical_cards', function (Builder $builder) use ($query) {
                return $builder->where('code', 'LIKE', "%$query%");
            });
        });
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    public static function whereVisibleToSponsor(Builder $builder): Builder
    {
        return $builder->where(static function(Builder $builder) {
            $builder->whereNotNull('employee_id');

            $builder->orWhere(static function(Builder $builder) {
                $builder->whereNull('employee_id');
                $builder->whereNull('product_id');
            });

            $builder->orWhere(static function(Builder $builder) {
                $builder->whereNull('employee_id');
                $builder->whereNotNull('product_id');
                $builder->whereNull('parent_id');
                $builder->where('returnable', false);
            });
        });
    }

    /**
     * @param Builder $builder
     * @param bool $inUse
     * @return Builder
     */
    public static function whereInUseQuery(Builder $builder, bool $inUse = true): Builder
    {
        return $builder->where(static function(Builder $builder) use ($inUse) {
            if ($inUse) {
                $builder->whereHas('transactions');
                $builder->orWhereHas('product_vouchers', function(Builder $builder) {
                    static::whereIsProductVoucher($builder);
                });
            } else {
                $builder->whereDoesntHave('transactions');
                $builder->whereDoesntHave('product_vouchers', function(Builder $builder) {
                    static::whereIsProductVoucher($builder);
                });
            }
        });
    }
    /**
     * @param array $options
     * @param Builder|null $builder
     * @return Builder
     * @throws \Exception
     */
    public static function whereInUseDateQuery(array $options, Builder $builder = null): Builder
    {
        if (count(array_filter(Arr::only($options, ['in_use_from', 'in_use_to']))) == 0) {
            throw new \Exception("At least one filter is required.");
        }

        $builder ?: Voucher::query();

        $baseQuery = $builder->addSelect([
            'transactions_date' => self::transactionOldest(),
            'product_vouchers_reservation_date' => self::productVouchersWithReservationsTransactionOldest(),
            'product_vouchers_date' => self::productVouchersWithoutReservationOldest(),
        ])->getQuery();

        $query = Voucher::query()->fromSub($baseQuery, 'vouchers');

        $datesSql = 'LEAST(
                Coalesce(`product_vouchers_date`,`product_vouchers_reservation_date`,`transactions_date`),
                Coalesce(`product_vouchers_reservation_date`,`product_vouchers_date`,`transactions_date`),
                Coalesce(`transactions_date`, `product_vouchers_date`,`product_vouchers_reservation_date`)
                )';

        $query->selectRaw($datesSql . ' as in_use_created_at');

        if ($from = Arr::get($options, 'in_use_from')) {
            $query->whereRaw($datesSql . ' >= "' . Carbon::parse($from)->startOfDay() . '"');
        }

        if ($to = Arr::get($options, 'in_use_to')) {
            $query->whereRaw($datesSql . ' <= "' . Carbon::parse($to)->startOfDay() . '"');
        }

        return $query;
    }

    /**
     * @return Builder
     */
    private static function transactionOldest(): Builder
    {
        return VoucherTransaction::whereColumn('voucher_transactions.voucher_id', 'vouchers.id')
            ->select('created_at')
            ->orderBy('created_at')
            ->limit(1);
    }

    /**
     * @return Builder
     */
    private static function productVouchersWithReservationsTransactionOldest(): Builder
    {
        return VoucherTransaction::whereIn('voucher_id', function(QBuilder $query) {
            $query->selectSub(
                Voucher::from('vouchers as product_voucher_reservations')
                    ->select('id')
                    ->whereColumn('product_voucher_reservations.parent_id', 'vouchers.id')
                    ->where(function(Builder $builder) {
                        VoucherQuery::whereIsProductVoucher($builder);
                    })
                    ->whereNotNull('product_reservation_id'), 'voucher_id');
        })
            ->select('created_at')
            ->orderBy('created_at')
            ->limit(1);
    }

    /**
     * @return Builder
     */
    private static function productVouchersWithoutReservationOldest(): Builder
    {
        return Voucher::from('vouchers as product_vouchers')
            ->whereColumn('product_vouchers.parent_id', 'vouchers.id')
            ->where(function(Builder $builder) {
                VoucherQuery::whereIsProductVoucher($builder);
            })
            ->whereNull('product_reservation_id')
            ->select('created_at')
            ->orderBy('created_at')
            ->limit(1);
    }

    /**
     * @param Builder $builder
     * @param bool $inUse
     * @return Builder
     */
    public static function whereIsProductVoucher(Builder $builder, bool $inUse = true): Builder
    {
        return $builder->where(static function(Builder $builder) use ($inUse) {
            $builder->whereNotNull('parent_id');

            $builder->where(static function(Builder $builder) use ($inUse) {
                $builder->whereDoesntHave('product_reservation');
                $builder->orWhereHas('product_reservation', function (Builder $builder) {
                    $builder->whereIn('state', [
                        ProductReservation::STATE_PENDING,
                        ProductReservation::STATE_ACCEPTED
                    ]);
                });
            });
        });
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    public static function whereNotInUseQuery(Builder $builder): Builder
    {
        return static::whereInUseQuery($builder, false);
    }
}
