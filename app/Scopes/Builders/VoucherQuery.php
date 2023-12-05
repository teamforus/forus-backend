<?php


namespace App\Scopes\Builders;

use App\Models\Fund;
use App\Models\ProductReservation;
use App\Models\Reimbursement;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QBuilder;
use Illuminate\Support\Facades\DB;

class VoucherQuery
{
    /**
     * @param Builder|Voucher $builder
     * @param string $identity_address
     * @param $fund_id
     * @param null $organization_id Provider organization id
     * @return Builder|Voucher
     */
    public static function whereProductVouchersCanBeScannedForFundBy(
        Builder|Voucher $builder,
        string $identity_address,
        $fund_id,
        $organization_id = null
    ): Builder|Voucher {
        $builder->whereHas('product', static function(Builder $builder) use (
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
     * @param Builder|Voucher $builder
     * @param string $q
     * @return Builder|Voucher
     */
    public static function whereSearchSponsorQuery(Builder|Voucher $builder, string $q): Builder|Voucher
    {
        return $builder->where(static function (Builder|Voucher $builder) use ($q) {
            $builder->where('note', 'LIKE', "%$q%");
            $builder->orWhere('activation_code', 'LIKE', "%$q%");
            $builder->orWhere('client_uid', 'LIKE', "%$q%");

            $builder->orWhereHas('identity', function (Builder $builder) use ($q) {
                $builder->whereRelation('primary_email', 'email', 'LIKE', "%$q%");
                $builder->orWhereRelation('record_bsn', 'value', 'LIKE', "%$q%");
            });

            $builder->orWhereHas('voucher_relation', function (Builder $builder) use ($q) {
                $builder->where('bsn', 'LIKE', "%$q%");
            });

            $builder->orWhereHas('physical_cards', function (Builder $builder) use ($q) {
                $builder->where('code', 'LIKE', "%$q%");
            });

            $builder->orWhereHas('voucher_records', function (Builder $builder) use ($q) {
                $builder->where('value', 'LIKE', "%$q%");
            });
        });
    }

    /**
     * @param Builder|Voucher $builder
     * @return Builder|Voucher
     */
    public static function whereVisibleToSponsor(Builder|Voucher $builder): Builder|Voucher
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
                        ProductReservation::STATE_WAITING,
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
    public static function whereIsProductVoucherWithoutTransactions(Builder $builder): Builder
    {
        return self::whereIsProductVoucher($builder)->whereDoesntHave('transactions');
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
        $selectQuery = Voucher::fromSub(self::addBalanceFields(Voucher::query()), 'vouchers');

        $selectQuery->where(function(Builder $builder) {
            $builder->where(fn(Builder $q) => static::whereIsProductVoucherWithoutTransactions($q));

            $builder->orWhere(function(Builder $builder) {
                $builder->whereNull('parent_id');
                $builder->where('balance', '>', 0);
            });
        });

        return $query->whereIn('id', $selectQuery->select('id'));
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
     * @param Relation|Builder|QBuilder $query
     * @return Relation|Builder|QBuilder
     */
    public static function addBalanceFields(Relation|Builder|QBuilder $query): Relation|Builder|QBuilder
    {
        $selectQuery = DB::query()->select([
            'amount_total' => static::voucherTotalAmountSubQuery(),
            'amount_spent' => static::voucherAmountSpentSubQuery(),
        ]);

        $query->addSelect([
            'balance' => DB::query()
                ->fromSub($selectQuery, 'voucher_payouts')
                ->selectRaw('`voucher_payouts`.`amount_total` - `voucher_payouts`.`amount_spent`'),
        ]);

        return $query;
    }

    /**
     * @return Builder
     */
    private static function voucherTotalAmountSubQuery(): Builder
    {
        return VoucherTransaction::query()
            ->where(fn ($builder) => VoucherTransactionQuery::whereIncoming($builder))
            ->whereColumn('vouchers.id', 'voucher_transactions.voucher_id')
            ->selectRaw('IFNULL(sum(voucher_transactions.amount), 0) + `vouchers`.`amount`');
    }

    /**
     * @return QBuilder
     */
    private static function voucherAmountSpentSubQuery(): QBuilder
    {
        $selectQuery = DB::query()->select([
            'transactions_amount' => VoucherTransaction::query()
                ->where(fn ($builder) => VoucherTransactionQuery::whereOutgoing($builder))
                ->whereColumn('vouchers.id', 'voucher_transactions.voucher_id')
                ->selectRaw('IFNULL(sum(voucher_transactions.amount), 0)'),
            'vouchers_amount' => Voucher::query()
                ->fromSub(Voucher::query(), 'product_vouchers')
                ->whereColumn('vouchers.id', 'product_vouchers.parent_id')
                ->selectRaw('IFNULL(sum(product_vouchers.amount), 0)'),
            'reimbursements_pending_amount' => Reimbursement::query()
                ->whereColumn('reimbursements.voucher_id', 'vouchers.id')
                ->where('reimbursements.state', Reimbursement::STATE_PENDING)
                ->selectRaw('IFNULL(sum(reimbursements.amount), 0)'),
        ]);

        return DB::query()
            ->fromSub($selectQuery, 'voucher_payouts')
            ->selectRaw('`transactions_amount` + `vouchers_amount` + `reimbursements_pending_amount`');
    }

    /**
     * @param Builder|Voucher|Relation $builder
     * @param Reimbursement|null $reimbursement
     * @return Builder|Voucher|Relation
     */
    public static function whereAllowReimbursements(
        Builder|Voucher|Relation $builder,
        ?Reimbursement $reimbursement = null,
    ): Builder|Voucher|Relation {
        return $builder->where(function(Builder|Voucher $builder) use ($reimbursement) {
            $builder->where(function(Builder|Voucher $builder) use ($reimbursement) {
                VoucherQuery::whereNotExpiredAndActive($builder);

                if ($reimbursement) {
                    $builder->orWhere('id', $reimbursement->voucher_id);
                }
            });

            $builder->whereNull('product_id');
            $builder->whereNull('product_reservation_id');
            $builder->whereRelation('fund', 'type', Fund::TYPE_BUDGET);
            $builder->whereRelation('fund.fund_config', 'allow_reimbursements', true);
        });
    }
}
