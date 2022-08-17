<?php


namespace App\Scopes\Builders;

use App\Models\ProductReservation;
use App\Models\Voucher;
use App\Models\Identity;
use App\Models\IdentityEmail;
use App\Models\VoucherTransaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QBuilder;
use Illuminate\Support\Facades\DB;

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
     * @param Builder|Relation $builder
     * @return Builder|Relation
     */
    public static function whereNotExpired(Builder|Relation $builder): Builder|Relation
    {
        return $builder->where(static function(Builder $builder) {
            $builder->where('vouchers.expire_at', '>=', today());

            $builder->whereHas('fund', static function(Builder $builder) {
                $builder->whereDate('end_date', '>=', today());
            });
        });
    }

    /**
     * @param Builder|Relation $builder
     * @return Builder|Relation
     */
    public static function whereActive(Builder|Relation $builder): Builder|Relation
    {
        return $builder->where('state', Voucher::STATE_ACTIVE);
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    public static function whereNotActive(Builder $builder): Builder
    {
        return $builder->where('state', '!=', Voucher::STATE_ACTIVE);
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
     * @param Builder|Relation $builder
     * @return Builder|Relation
     */
    public static function whereNotExpiredAndActive(Builder|Relation $builder): Builder|Relation
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
     * @return Builder
     */
    public static function whereExpiredOrNotActive(Builder $builder): Builder
    {
        return $builder->where(function (Builder $builder) {
            $builder->where(fn (Builder $builder) => static::whereExpired($builder));
            $builder->orWhere(fn (Builder $builder) => static::whereNotActive($builder));
        });
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

            if ($addresses = IdentityEmail::searchByEmail($query)->pluck('identity_address')) {
                $builder->orWhereIn('identity_address', $addresses);
            }

            if ($bsn_identities = Identity::searchByBsn($query)?->pluck('address')) {
                $builder->orWhereIn('identity_address', $bsn_identities->toArray());
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
     * @param Builder|Relation $builder
     * @param Carbon|null $fromDate
     * @param Carbon|null $toDate
     * @return Builder
     */
    public static function whereInUseDateQuery(
        Builder|Relation $builder,
        Carbon $fromDate = null,
        Carbon $toDate = null,
    ): Builder {
        if ($fromDate) {
            $builder->where("first_use_date", '>=', $fromDate);
        }

        if ($toDate) {
            $builder->where("first_use_date", '<=', $toDate);
        }

        return $builder;
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    public static function whereIsProductVoucher(Builder $builder): Builder
    {
        return $builder->where(static function(Builder $builder) {
            $builder->whereNotNull('parent_id');

            $builder->where(static function(Builder $builder) {
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

    /**
     * @param Relation|Builder $query
     * @return Relation|Builder
     */
    public static function whereHasBalance(Relation|Builder $query): Relation|Builder
    {
        return $query->where(function(Builder $builder) {
            $builder->where(fn(Builder $builder) => static::whereIsProductVoucher(
                $builder->whereDoesntHave('transactions')
            ));

            $builder->orWhere(function(Builder $builder) {
                $builder->whereNull('parent_id');
                $builder->where('amount', '>', fn (QBuilder $builder) => $builder->fromSub(
                    self::voucherBalanceSubQuery(), 'voucher_payouts'
                ));
            });
        });
    }

    /**
     * @param Relation|Builder $query
     * @return Relation|Builder
     */
    public static function whereHasBalanceIsActiveAndNotExpired(
        Relation|Builder $query
    ): Relation|Builder {
        return self::whereHasBalance(self::whereNotExpiredAndActive($query));
    }

    /**
     * @param Relation|Builder $query
     * @return Relation|Builder
     */
    public static function addBalanceFields(Relation|Builder $query): Relation|Builder
    {
        $query->addSelect([
            'balance' => DB::query()
                ->fromSub(static::voucherBalanceSubQuery(), 'voucher_payouts')
                ->selectRaw('vouchers.amount - voucher_payouts.amount_spent'),
        ]);

        return $query;
    }

    /**
     * @return QBuilder
     */
    private static function voucherBalanceSubQuery(): QBuilder
    {
        $selectQuery = DB::query()->select([
            'transactions_amount' => VoucherTransaction::query()
                ->whereColumn('vouchers.id', 'voucher_transactions.voucher_id')
                ->selectRaw('IFNULL(sum(voucher_transactions.amount), 0)'),
            'vouchers_amount' => Voucher::query()
                ->fromSub(Voucher::query(), 'product_vouchers')
                ->whereColumn('vouchers.id', 'product_vouchers.parent_id')
                ->selectRaw('IFNULL(sum(product_vouchers.amount), 0)'),
        ]);

        return DB::query()
            ->fromSub($selectQuery, 'voucher_payouts')
            ->selectRaw('`transactions_amount` + `vouchers_amount` as `amount_spent`');
    }
}
