<?php


namespace App\Statistics\Funds;

use App\Models\Fund;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Services\BankService\Models\Bank;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class FinancialOverviewStatisticQueries
{
    /**
     * @param Builder|Relation $builder
     * @param Carbon $from
     * @param Carbon $to
     * @return Builder|Relation
     */
    public static function whereNotExpired(Builder|Relation $builder, Carbon $from, Carbon $to): Builder|Relation
    {
        return $builder->where(static function(Builder $builder) use ($from, $to) {
            $builder->where('vouchers.created_at', '>=', $from);
            $builder->where('vouchers.created_at', '<=', $to);
            $builder->where('vouchers.expire_at', '>=', $to);

            $builder->whereHas('fund', static function(Builder $builder) use ($from, $to) {
                $builder->where('start_date', '<=', $to);
                $builder->where('end_date', '>=', $from);
            });
        });
    }

    /**
     * @param Builder|Relation $builder
     * @param Carbon $from
     * @param Carbon $to
     * @return Builder|Relation
     */
    public static function whereNotExpiredAndActive(Builder|Relation $builder, Carbon $from, Carbon $to): Builder|Relation
    {
        return self::whereNotExpired(self::whereActive($builder, $from, $to), $from, $to);
    }

    /**
     * @param Builder|Relation $builder
     * @param Carbon $from
     * @param Carbon $to
     * @return Builder|Relation|Voucher
     */
    public static function whereActive(Builder|Relation $builder, Carbon $from, Carbon $to): Builder|Relation|Voucher
    {
        return $builder->where(static function (Builder $builder) use ($from, $to) {
            $builder->where('state', Voucher::STATE_ACTIVE);
            $builder->orWhere(static function (Builder $builder) use ($from, $to) {
                $builder->where('created_at', '>=', $from);
                $builder->where('created_at', '<=', $to);
                $builder->where('expire_at', '>=', $from);
            });
        });
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
     * @param $from
     * @param $to
     * @return Builder
     */
    public static function whereNotExpiredAndPending(Builder $builder, $from, $to): Builder
    {
        return self::whereNotExpired(self::wherePending($builder), $from, $to);
    }

    /**
     * @param Builder $builder
     * @param Carbon $from
     * @param Carbon $to
     * @return Builder
     */
    public static function whereNotExpiredAndDeactivated(
        Builder $builder,
        Carbon $from,
        Carbon $to,
    ): Builder {
        return self::whereNotExpired(self::whereDeactivated($builder), $from, $to);
    }

    /**
     * @param Fund $fund
     * @return float
     */
    public static function getFundBudgetTotal(Fund $fund): float
    {
        if ($fund->balance_provider === Fund::BALANCE_PROVIDER_TOP_UPS) {
            return round($fund->top_up_transactions()->sum('amount'), 2);
        }

        if ($fund->balance_provider === Fund::BALANCE_PROVIDER_BANK_CONNECTION) {
            return round(floatval($fund->balance) + self::getFundBudgetUsed($fund), 2);
        }

        return 0;
    }

    /**
     * @param Fund $fund
     * @return float
     */
    public static function getFundBudgetUsed(Fund $fund): float
    {
        return round($fund->voucher_transactions()->sum('voucher_transactions.amount'), 2);
    }

    /**
     * @param Fund $fund
     * @return float
     */
    public static function getFundBudgetLeft(Fund $fund): float
    {
        if ($fund->balance_provider === Fund::BALANCE_PROVIDER_TOP_UPS) {
            return round(
                self::getFundBudgetTotal($fund) - self::getFundBudgetUsed($fund),
                2
            );
        }

        if ($fund->balance_provider === Fund::BALANCE_PROVIDER_BANK_CONNECTION) {
            return round($fund->balance, 2);
        }

        return 0;
    }

    /**
     * @param Fund $fund
     * @param Carbon $from
     * @param Carbon $to
     * @return float
     */
    public static function getBudgetFundUsedActiveVouchers(Fund $fund, Carbon $from, Carbon $to): float
    {
        return round($fund->voucher_transactions()
            ->where('voucher_transactions.created_at', '>=', $from)
            ->where('voucher_transactions.created_at', '<=', $to)
            ->where('vouchers.expire_at', '>=', $from)
            ->sum('voucher_transactions.amount'), 2);
    }

    /**
     * @param Fund $fund
     * @return float
     */
    public static function getFundTransactionCosts(Fund $fund): float
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
                ->count() * $bank->transaction_cost;
        }

        $costs += $fund->voucher_transactions()
                ->where('voucher_transactions.amount', '>', 0)
                ->where('voucher_transactions.state', $state)
                ->whereIn('voucher_transactions.target', $targets)
                ->whereDoesntHave('voucher_transaction_bulk')
                ->count() * $targetCostOld;

        return $costs;
    }
}