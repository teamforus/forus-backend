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
     * @param Builder|Relation|Voucher $builder
     * @param string $identity_address
     * @param $fund_id
     * @param null $organization_id Provider organization id
     * @return Builder|Relation|Voucher
     */
    public static function whereProductVouchersCanBeScannedForFundBy(
        Builder|Relation|Voucher $builder,
        string $identity_address,
        $fund_id,
        $organization_id = null
    ): Builder|Relation|Voucher {
        $builder->whereHas('product', static function (Builder $builder) use (
            $fund_id,
            $identity_address,
            $organization_id
        ) {
            $builder->whereHas('organization', static function (Builder $builder) use (
                $fund_id,
                $identity_address,
                $organization_id
            ) {
                if ($organization_id instanceof Builder) {
                    $builder->whereIn('organizations.id', $organization_id);
                } else {
                    $builder->whereIn('organizations.id', (array) $organization_id);
                }

                OrganizationQuery::whereHasPermissions($builder, $identity_address, 'scan_vouchers');

                $builder->whereHas('fund_providers', static function (Builder $builder) use ($fund_id) {
                    FundProviderQuery::whereApprovedForFundsFilter($builder, $fund_id, 'product');
                });
            });

            ProductQuery::approvedForFundsFilter($builder, $fund_id);
        });

        return $builder->where(function (Builder $builder) {
            $builder->whereDoesntHave('transactions');
            $builder->whereDoesntHave('product_reservation', function (Builder $builder) {
                $builder->where('state', '!=', ProductReservation::STATE_PENDING);
                $builder->orWhereDate('expire_at', '<', now());
            });
        });
    }

    /**
     * @param Builder|Relation|Voucher $builder
     * @return Builder|Relation|Voucher
     */
    public static function whereNotExpired(Builder|Relation|Voucher $builder): Builder|Relation|Voucher
    {
        return $builder->where(static function (Builder $builder) {
            $builder->where('vouchers.expire_at', '>=', today());

            $builder->whereHas('fund', static function (Builder $builder) {
                $builder->whereDate('end_date', '>=', today());
            });
        });
    }

    /**
     * @param Builder|Relation|Voucher $builder
     * @return Builder|Relation|Voucher
     */
    public static function whereActive(Builder|Relation|Voucher $builder): Builder|Relation|Voucher
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
     * @param Builder|Relation|Voucher $builder
     * @return Builder|Relation|Voucher
     */
    public static function whereDeactivated(
        Builder|Relation|Voucher $builder,
    ): Builder|Relation|Voucher {
        return $builder->where('state', Voucher::STATE_DEACTIVATED);
    }

    /**
     * @param Builder|Relation|Voucher $builder
     * @return Builder|Relation|Voucher
     */
    public static function whereNotExpiredAndActive(
        Builder|Relation|Voucher $builder
    ): Builder|Relation|Voucher {
        return self::whereNotExpired(self::whereActive($builder));
    }

    /**
     * @param Builder|Relation|Voucher $builder
     * @return Builder|Relation|Voucher
     */
    public static function whereExpired(
        Builder|Relation|Voucher $builder,
    ): Builder|Relation|Voucher {
        return $builder->where(static function (Builder $builder) {
            $builder->where('vouchers.expire_at', '<', today());

            $builder->orWhereHas('fund', static function (Builder $builder) {
                $builder->where('end_date', '<', today());
            });
        });
    }

    /**
     * @param Builder|Relation|Voucher $builder
     * @return Builder|Relation|Voucher
     */
    public static function whereExpiredButActive(
        Builder|Relation|Voucher $builder,
    ): Builder|Relation|Voucher {
        return static::whereActive(static::whereExpired($builder));
    }

    /**
     * @param Builder|Relation|Voucher $builder
     * @return Builder|Relation|Voucher
     */
    public static function whereExpiredOrNotActive(
        Builder|Relation|Voucher $builder,
    ): Builder|Relation|Voucher {
        return $builder->where(function (Builder $builder) {
            $builder->where(fn (Builder $builder) => static::whereExpired($builder));
            $builder->orWhere(fn (Builder $builder) => static::whereNotActive($builder));
        });
    }

    /**
     * @param Builder|Relation|Voucher $builder
     * @return Builder|Relation|Voucher
     */
    public static function whereVisibleToSponsor(
        Builder|Relation|Voucher $builder
    ): Builder|Relation|Voucher {
        return $builder->where(static function (Builder $builder) {
            $builder->whereNotNull('employee_id');

            $builder->orWhere(static function (Builder $builder) {
                $builder->whereNull('employee_id');
                $builder->whereNull('product_id');
            });

            $builder->orWhere(static function (Builder $builder) {
                $builder->whereNull('employee_id');
                $builder->whereNotNull('product_id');
                $builder->whereNull('parent_id');
                $builder->where('returnable', false);
            });
        });
    }

    /**
     * @param Builder|Relation|Voucher $builder
     * @param string $q
     * @return Builder|Relation|Voucher
     */
    public static function whereSearchSponsorQuery(
        Builder|Relation|Voucher $builder,
        string $q,
    ): Builder|Relation|Voucher {
        return $builder->where(static function ($builder) use ($q) {
            $builder->where('number', 'LIKE', '%' . ltrim($q, '#') . '%');
            $builder->orWhere('note', 'LIKE', "%$q%");
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
     * @param Builder|Relation|Voucher $builder
     * @param bool $inUse
     * @return Builder|Relation|Voucher
     */
    public static function whereInUseQuery(
        Builder|Relation|Voucher $builder,
        bool $inUse = true,
    ): Builder|Relation|Voucher {
        return $builder->where(static function (Builder $builder) use ($inUse) {
            if ($inUse) {
                $builder->whereHas('transactions');
                $builder->orWhereHas('product_vouchers');
            } else {
                $builder->whereDoesntHave('transactions');
                $builder->whereDoesntHave('product_vouchers');
            }
        });
    }

    /**
     * @param Builder|Relation|Voucher $builder
     * @param Carbon|null $fromDate
     * @param Carbon|null $toDate
     * @return Builder|Relation|Voucher
     */
    public static function whereInUseDateQuery(
        Builder|Relation|Voucher $builder,
        Carbon $fromDate = null,
        Carbon $toDate = null,
    ): Builder|Relation|Voucher {
        if ($fromDate) {
            $builder->where('first_use_date', '>=', $fromDate);
        }

        if ($toDate) {
            $builder->where('first_use_date', '<=', $toDate);
        }

        return $builder;
    }

    /**
     * @param Builder|Relation|Voucher $builder
     * @return Builder|Relation|Voucher
     */
    public static function whereIsProductVoucher(
        Builder|Relation|Voucher $builder,
    ): Builder|Relation|Voucher {
        return $builder->where(static function (Builder $builder) {
            $builder->whereNotNull('parent_id');

            $builder->where(static function (Builder $builder) {
                $builder->whereDoesntHave('product_reservation');
                $builder->orWhereHas('product_reservation', function (Builder $builder) {
                    $builder->whereIn('state', [
                        ProductReservation::STATE_WAITING,
                        ProductReservation::STATE_PENDING,
                        ProductReservation::STATE_ACCEPTED,
                    ]);
                });
            });
        });
    }

    /**
     * @param Builder|Relation|Voucher $builder
     * @return Builder|Relation|Voucher
     */
    public static function whereIsProductVoucherWithoutTransactions(
        Builder|Relation|Voucher $builder
    ): Builder|Relation|Voucher {
        return self::whereIsProductVoucher($builder)->whereDoesntHave('transactions');
    }

    /**
     * @param Builder|Relation|Voucher $builder
     * @return Builder|Relation|Voucher
     */
    public static function whereNotInUseQuery(
        Builder|Relation|Voucher $builder,
    ): Builder|Relation|Voucher {
        return static::whereInUseQuery($builder, false);
    }

    /**
     * @param Builder|Relation|Voucher $query
     * @return Builder|Relation|Voucher
     */
    public static function whereHasBalance(
        Builder|Relation|Voucher $query
    ): Builder|Relation|Voucher {
        $selectQuery = self::addBalanceFields(Voucher::query());

        $selectQuery->where(function (Builder $builder) {
            $builder->where(fn (Builder $q) => static::whereIsProductVoucherWithoutTransactions($q));

            $builder->orWhere(function (Builder $builder) {
                $builder->whereNull('parent_id');
                $builder->having('balance', '>', 0);
            });
        });

        return $query->whereIn('id', $selectQuery->select('id'));
    }

    /**
     * @param Builder|Relation|Voucher $query
     * @return Builder|Relation|Voucher
     */
    public static function whereHasBalanceIsActiveAndNotExpired(
        Builder|Relation|Voucher $query,
    ): Builder|Relation|Voucher {
        return self::whereHasBalance(self::whereNotExpiredAndActive($query));
    }

    /**
     * @param Builder|Relation|Voucher $query
     * @return Builder|Relation|Voucher
     */
    public static function addBalanceFields(
        Builder|Relation|Voucher $query,
    ): Builder|Relation|Voucher {
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
     * @param Builder|Relation|Voucher $builder
     * @param Reimbursement|null $reimbursement
     * @return Builder|Relation|Voucher
     */
    public static function whereAllowReimbursements(
        Builder|Relation|Voucher $builder,
        ?Reimbursement $reimbursement = null,
    ): Builder|Relation|Voucher {
        return $builder->where(function (Builder $builder) use ($reimbursement) {
            $builder->where(function (Builder $builder) use ($reimbursement) {
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

    /**
     * @return Builder|VoucherTransaction
     */
    private static function voucherTotalAmountSubQuery(): Builder|VoucherTransaction
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
            'vouchers_amount' => VoucherSubQuery::getReservationOrProductVoucherSubQuery()
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
}
