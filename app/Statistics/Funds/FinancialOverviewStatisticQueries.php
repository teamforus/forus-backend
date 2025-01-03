<?php


namespace App\Statistics\Funds;

use App\Models\Fund;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Scopes\Builders\VoucherQuery;
use App\Services\BankService\Models\Bank;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class FinancialOverviewStatisticQueries
{
    /**
     * @param Builder|Relation $builder
     * @param Carbon|null $from
     * @param Carbon|null $to
     * @return Builder|Relation
     */
    public static function whereDate(Builder|Relation $builder, ?Carbon $from, ?Carbon $to): Builder|Relation
    {
        return $builder->where(static function(Builder $builder) use ($from, $to) {
            if ($from) {
                $builder->where('vouchers.created_at', '>=', $from);
            }

            if ($to) {
                $builder->where('vouchers.created_at', '<=', $to);
            }

            $builder->whereHas('fund', static function(Builder $builder) use ($from, $to) {
                if ($from) {
                    $builder->where('end_date', '>=', $from);
                }

                if ($to) {
                    $builder->where('start_date', '<=', $to);
                }
            });
        });
    }

    /**
     * @param Builder|Relation $builder
     * @return Builder|Relation
     */
    public static function whereNotExpired(Builder|Relation $builder): Builder|Relation
    {
        return VoucherQuery::whereNotExpired($builder);
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
     * @param Builder|Relation|Voucher $builder
     * @return Builder|Relation|Voucher
     */
    public static function whereActive(
        Builder|Relation|Voucher $builder,
    ): Builder|Relation|Voucher {
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
     * @param Fund $fund
     * @param Carbon|null $from
     * @param Carbon|null $to
     * @return float
     */
    public static function getFundBudgetTotal(
        Fund $fund,
        ?Carbon $from = null,
        ?Carbon $to = null,
    ): float {
        $builder = $fund->top_up_transactions();

        if ($from) {
            $builder->where('fund_top_up_transactions.created_at', '>=', $from);
        }

        if ($to) {
            $builder->where('fund_top_up_transactions.created_at', '<=', $to);
        }

        return round($builder->sum('amount'), 2);
    }

    /**
     * @param Fund $fund
     * @param Carbon|null $from
     * @param Carbon|null $to
     * @return float
     */
    public static function getFundBudgetUsed(Fund $fund, ?Carbon $from, ?Carbon $to): float
    {
        return round($fund->voucher_transactions()
            ->where(function (Builder $builder) use ($from, $to) {
                if ($from) {
                    $builder->where('voucher_transactions.created_at', '>=', $from);
                }

                if ($to) {
                    $builder->where('voucher_transactions.created_at', '<=', $to);
                }
            })
            ->where('voucher_transactions.state', VoucherTransaction::STATE_SUCCESS)
            ->sum('voucher_transactions.amount'), 2);
    }

    /**
     * @param Fund $fund
     * @param Carbon|null $from
     * @param Carbon|null $to
     * @return float
     */
    public static function getBudgetFundUsedActiveVouchers(
        Fund $fund,
        ?Carbon $from = null,
        ?Carbon $to = null,
    ): float {
        return round($fund->voucher_transactions()
            ->where(function (Builder $builder) use ($from, $to) {
                if ($from) {
                    $builder->where('vouchers.expire_at', '>=', $from);
                    $builder->where('voucher_transactions.created_at', '>=', $from);
                }

                if ($to) {
                    $builder->where('voucher_transactions.created_at', '<=', $to);
                }
            })
            ->sum('voucher_transactions.amount'), 2);
    }

    /**
     * @param Fund $fund
     * @param Carbon|null $from
     * @param Carbon|null $to
     * @return float
     */
    public static function getFundTransactionCosts(Fund $fund, ?Carbon $from, ?Carbon $to): float
    {
        $costs = 0;
        $state = VoucherTransaction::STATE_SUCCESS;
        $targets = VoucherTransaction::TARGETS_OUTGOING;
        $targetCostOld = VoucherTransaction::TRANSACTION_COST_OLD;

        foreach (Bank::get() as $bank) {
            $costs += $fund->voucher_transactions()
                ->where('voucher_transactions.amount', '>', 0)
                ->where('voucher_transactions.state', $state)
                ->whereIn('voucher_transactions.target', $targets)
                ->whereRelation('voucher_transaction_bulk.bank_connection', 'bank_id', $bank->id)
                ->where(function(Builder $builder) use ($from, $to) {
                    if ($from) {
                        $builder->where('voucher_transactions.created_at', '>=', $from);
                    }

                    if ($to) {
                        $builder->where('voucher_transactions.created_at', '<=', $to);
                    }
                })
                ->count() * $bank->transaction_cost;
        }

        $costs += $fund->voucher_transactions()
                ->where('voucher_transactions.amount', '>', 0)
                ->where('voucher_transactions.state', $state)
                ->whereIn('voucher_transactions.target', $targets)
                ->whereDoesntHave('voucher_transaction_bulk')
                ->where(function(Builder $builder) use ($from, $to) {
                    if ($from) {
                        $builder->where('voucher_transactions.created_at', '>=', $from);
                    }

                    if ($to) {
                        $builder->where('voucher_transactions.created_at', '<=', $to);
                    }
                })
                ->count() * $targetCostOld;

        return $costs;
    }
}