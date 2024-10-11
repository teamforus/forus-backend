<?php


namespace App\Statistics\Funds;

use App\Models\Fund;
use App\Models\Organization;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class FinancialStatistic
 * @package App\Statistics
 */
class FinancialOverviewStatistic
{
    /**
     * @param Organization $organization
     * @param int $year
     * @return array
     */
    public function getStatistics(Organization $organization, int $year): array
    {
        $from = Carbon::createFromFormat('Y', $year)->startOfYear();
        $to = $year < now()->year ? Carbon::createFromFormat('Y', $year)->endOfYear() : now();

        return [
            'funds' => $this->getFundTotals($this->getFunds($organization), $from, $to),
            'budget_funds' => $this->getFundTotals($this->getBudgetFunds($organization), $from, $to),
        ];
    }

    /**
     * @param Organization $organization
     * @return Collection|Arrayable
     */
    private function getFunds(Organization $organization): Collection|Arrayable
    {
        return $organization->funds()->where('archived', false)->where(
            'state', '!=', Fund::STATE_WAITING
        )->get();
    }

    /**
     * @param Organization $organization
     * @return Collection|Arrayable
     */
    private function getBudgetFunds(Organization $organization): Collection|Arrayable {
        return $this->getFunds($organization)->where('type', Fund::TYPE_BUDGET);
    }

    /**
     * @param Collection $funds
     * @param Carbon $from
     * @param Carbon $to
     * @return array
     */
    private function getFundTotals(Collection $funds, Carbon $from, Carbon $to): array {
        $budget = 0;
        $budget_left = 0;
        $budget_used = 0;
        $budget_used_active_vouchers = 0;
        $transaction_costs = 0;

        $query = Voucher::query()->whereNull('parent_id')->whereIn('fund_id', $funds->pluck('id'));
        $vouchersQuery = FinancialOverviewStatisticQueries::whereNotExpired($query, $from, $to);
        $activeVouchersQuery = FinancialOverviewStatisticQueries::whereNotExpiredAndActive((clone $vouchersQuery), $from, $to);
        $inactiveVouchersQuery = FinancialOverviewStatisticQueries::whereNotExpiredAndPending((clone $vouchersQuery), $from, $to);
        $deactivatedVouchersQuery = FinancialOverviewStatisticQueries::whereNotExpiredAndDeactivated((clone $vouchersQuery), $from, $to);

        $vouchersAmount = $vouchersQuery->sum('amount');
        $activeVouchersAmount = $activeVouchersQuery->sum('amount');
        $inactiveVouchersAmount = $inactiveVouchersQuery->sum('amount');
        $deactivatedVouchersAmount = $deactivatedVouchersQuery->sum('amount');

        $vouchers_amount = currency_format($vouchersAmount);
        $active_vouchers_amount = currency_format($activeVouchersAmount);
        $inactive_vouchers_amount = currency_format($inactiveVouchersAmount);
        $deactivated_vouchers_amount = currency_format($deactivatedVouchersAmount);

        $vouchers_amount_locale = currency_format_locale($vouchersAmount);
        $active_vouchers_amount_locale = currency_format_locale($activeVouchersAmount);
        $inactive_vouchers_amount_locale = currency_format_locale($inactiveVouchersAmount);
        $deactivated_vouchers_amount_locale = currency_format_locale($deactivatedVouchersAmount);

        $vouchers_count = $vouchersQuery->count();
        $active_vouchers_count = $activeVouchersQuery->count();
        $inactive_vouchers_count = $inactiveVouchersQuery->count();
        $deactivated_vouchers_count = $deactivatedVouchersQuery->count();

        foreach ($funds as $fund) {
            $budget += FinancialOverviewStatisticQueries::getFundBudgetTotal($fund, $from, $to);
            $budget_left += FinancialOverviewStatisticQueries::getFundBudgetLeft($fund, $from, $to);
            $budget_used += FinancialOverviewStatisticQueries::getFundBudgetUsed($fund, $from, $to);
            $budget_used_active_vouchers += FinancialOverviewStatisticQueries::getBudgetFundUsedActiveVouchers($fund, $from, $to);
            $transaction_costs += FinancialOverviewStatisticQueries::getFundTransactionCosts($fund, $from, $to);
        }

        $budget_locale = currency_format_locale($budget);
        $budget_left_locale = currency_format_locale($budget_left);
        $budget_used_locale = currency_format_locale($budget_used);
        $budget_used_active_vouchers_locale = currency_format_locale($budget_used_active_vouchers);
        $transaction_costs_locale = currency_format_locale($transaction_costs);

        return compact(
            'budget', 'budget_left',
            'budget_used', 'budget_used_active_vouchers', 'transaction_costs',
            'vouchers_amount', 'vouchers_count', 'active_vouchers_amount', 'active_vouchers_count',
            'inactive_vouchers_amount', 'inactive_vouchers_count',
            'deactivated_vouchers_amount', 'deactivated_vouchers_count',
            'vouchers_amount_locale', 'active_vouchers_amount_locale',
            'inactive_vouchers_amount_locale', 'deactivated_vouchers_amount_locale',
            'budget_locale', 'budget_left_locale', 'budget_used_locale',
            'budget_used_active_vouchers_locale', 'transaction_costs_locale',
        );
    }
}