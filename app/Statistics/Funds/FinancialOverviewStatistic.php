<?php

namespace App\Statistics\Funds;

use App\Models\Employee;
use App\Models\Fund;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Voucher;
use App\Models\VoucherRecord;
use App\Models\VoucherTransaction;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;

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
        $to = Carbon::createFromFormat('Y', $year)->endOfYear();

        return [
            'funds' => $this->getFundTotals($this->getFunds($organization), $from, $to),
            'budget_funds' => $this->getFundTotals($this->getBudgetFunds($organization), $from, $to),
            'year' => $year,
        ];
    }

    /**
     * @param Fund $fund
     * @param string|null $stats
     * @param int|null $year
     * @return array
     */
    public static function getFinancialData(Fund $fund, ?string $stats = null, ?int $year = null): array
    {
        $from = $year ? Carbon::createFromFormat('Y', $year)->startOfYear() : null;
        $to = $year ? Carbon::createFromFormat('Y', $year)->endOfYear() : null;

        if ($stats == 'min') {
            $used = FinancialOverviewStatisticQueries::getFundBudgetUsed($fund, $from, $to);
            $total = FinancialOverviewStatisticQueries::getFundBudgetTotal($fund, $from, $to);
            $left = round($total - $used, 2);

            return [
                'budget' => [
                    'left' => currency_format($left),
                    'left_locale' => currency_format_locale($left),
                    'used' => currency_format($used),
                    'used_locale' => currency_format_locale($used),
                    'total' => currency_format($total),
                    'total_locale' => currency_format_locale($total),
                ],
            ];
        }

        $approvedCount = $fund->provider_organizations_approved;
        $providersEmployeeCount = $approvedCount->map(function (Organization $organization) {
            return $organization->employees->count();
        })->sum();

        $validatorsCount = $fund->organization->employees->filter(function (Employee $employee) {
            return $employee->roles->filter(function (Role $role) {
                return $role->permissions->where('key', Permission::VALIDATE_RECORDS)->isNotEmpty();
            });
        })->count();

        $loadBudgetStats = $stats == 'all' || $stats == 'budget';
        $loadProductVouchersStats = $stats == 'all' || $stats == 'product_vouchers';
        $loadPayoutsStats = $stats == 'all' || $stats == 'payout_vouchers';

        return [
            'sponsor_count' => $fund->organization->employees->count(),
            'provider_organizations_count' => $fund->provider_organizations_approved->count(),
            'provider_employees_count' => $providersEmployeeCount,
            'validators_count' => $validatorsCount,
            'budget' => $loadBudgetStats ?
                self::getVoucherData($fund, 'budget', $from, $to) : null,
            'product_vouchers' => $loadProductVouchersStats ?
                self::getVoucherData($fund, 'product', $from, $to) : null,
            'payout_vouchers' => $loadPayoutsStats ?
                self::getVoucherData($fund, 'payout', $from, $to) : null,
        ];
    }

    /**
     * @param Builder|Relation $vouchersQuery
     * @param Carbon|null $from
     * @param Carbon|null $to
     * @return array
     */
    public static function getFundDetails(
        Builder|Relation $vouchersQuery,
        ?Carbon $from,
        ?Carbon $to,
    ): array {
        $vouchersQuery = FinancialOverviewStatisticQueries::whereDate($vouchersQuery, $from, $to);
        $activeVouchersQuery = FinancialOverviewStatisticQueries::whereNotExpiredAndActive((clone $vouchersQuery));
        $inactiveVouchersQuery = FinancialOverviewStatisticQueries::wherePending((clone $vouchersQuery));
        $deactivatedVouchersQuery = FinancialOverviewStatisticQueries::whereDeactivated((clone $vouchersQuery));

        $vouchers_count = $vouchersQuery->count();
        $inactive_count = $inactiveVouchersQuery->count();
        $active_count = $activeVouchersQuery->count();
        $deactivated_count = $deactivatedVouchersQuery->count();
        $inactive_percentage = $inactive_count ? $inactive_count / $vouchers_count * 100 : 0;

        return [
            'reserved' => $activeVouchersQuery->sum('amount'),
            'vouchers_amount' => $vouchersQuery->sum('amount'),
            'vouchers_count' => $vouchers_count,
            'active_amount' => $activeVouchersQuery->sum('amount'),
            'active_count' => $active_count,
            'inactive_amount' => $inactiveVouchersQuery->sum('amount'),
            'inactive_count' => $inactive_count,
            'inactive_percentage' => currency_format($inactive_percentage),
            'deactivated_amount' => $deactivatedVouchersQuery->sum('amount'),
            'deactivated_count' => $deactivated_count,
            'children_count' => self::getVoucherChildrenCount($vouchersQuery),
        ];
    }

    /**
     * @param Builder|Relation|Voucher $vouchersQuery
     * @param Carbon|null $from
     * @param Carbon|null $to
     * @return array
     */
    public static function getFundPayoutDetails(
        Builder|Relation|Voucher $vouchersQuery,
        ?Carbon $from,
        ?Carbon $to,
    ): array {
        $vouchersQuery = FinancialOverviewStatisticQueries::whereDate($vouchersQuery, $from, $to);
        $vouchersQuery->whereRelation('transactions', 'state', VoucherTransaction::STATE_SUCCESS);

        return [
            'vouchers_amount' => $vouchersQuery->sum('amount'),
            'vouchers_count' => $vouchersQuery->count(),
        ];
    }

    /**
     * @param Fund $fund
     * @param string $type
     * @param Carbon|null $from
     * @param Carbon|null $to
     * @return array
     */
    protected static function getVoucherData(
        Fund $fund,
        string $type,
        ?Carbon $from,
        ?Carbon $to,
    ): array {
        $details = match($type) {
            'budget' => self::getFundDetails($fund->budget_vouchers()->getQuery(), $from, $to),
            'product' => self::getFundDetails($fund->product_vouchers()->getQuery(), $from, $to),
            'payout' => self::getFundPayoutDetails($fund->payout_vouchers()->getQuery(), $from, $to),
        };

        if ($type === 'payout') {
            return [
                'vouchers_count' => $details['vouchers_count'],
                'vouchers_amount' => currency_format($details['vouchers_amount']),
                'vouchers_amount_locale' => currency_format_locale($details['vouchers_amount']),
            ];
        }

        $budgetData = $type == 'budget' ? self::getVoucherDataBudget($fund, $from, $to) : [];

        return [
            ...$budgetData,
            'children_count' => $details['children_count'],
            'vouchers_count' => $details['vouchers_count'],
            'vouchers_amount' => currency_format($details['vouchers_amount']),
            'vouchers_amount_locale' => currency_format_locale($details['vouchers_amount']),
            'active_vouchers_amount' => currency_format($details['active_amount']),
            'active_vouchers_amount_locale' => currency_format_locale($details['active_amount']),
            'active_vouchers_count' => $details['active_count'],
            'inactive_vouchers_amount' => currency_format($details['inactive_amount']),
            'inactive_vouchers_amount_locale' => currency_format_locale($details['inactive_amount']),
            'inactive_vouchers_count' => $details['inactive_count'],
            'deactivated_vouchers_amount' => currency_format($details['deactivated_amount']),
            'deactivated_vouchers_amount_locale' => currency_format_locale($details['deactivated_amount']),
            'deactivated_vouchers_count' => $details['deactivated_count'],
        ];
    }

    /**
     * @param Fund $fund
     * @param Carbon|null $from
     * @param Carbon|null $to
     * @return array
     */
    protected static function getVoucherDataBudget(Fund $fund, ?Carbon $from, ?Carbon $to): array
    {
        $total = FinancialOverviewStatisticQueries::getFundBudgetTotal($fund, $from, $to);
        $used = FinancialOverviewStatisticQueries::getFundBudgetUsed($fund, $from, $to);
        $left = round($total - $used, 2);

        $transactionCosts = FinancialOverviewStatisticQueries::getFundTransactionCosts($fund, $from, $to);
        $usedActiveVouchers = FinancialOverviewStatisticQueries::getBudgetFundUsedActiveVouchers($fund, $from, $to);

        return [
            'total' => currency_format($total),
            'total_locale' => currency_format_locale($total),
            'validated' => currency_format($fund->budget_validated),
            'used' => currency_format($used),
            'used_locale' => currency_format_locale($used),
            'used_active_vouchers' => currency_format($usedActiveVouchers),
            'used_active_vouchers_locale' => currency_format_locale($usedActiveVouchers),
            'left' => currency_format($left),
            'left_locale' => currency_format_locale($left),
            'transaction_costs' => currency_format($transactionCosts),
            'transaction_costs_locale' => currency_format_locale($transactionCosts),
        ];
    }

    /**
     * @param Builder $vouchersQuery
     * @return mixed
     */
    protected static function getVoucherChildrenCount(Builder $vouchersQuery): mixed
    {
        return VoucherRecord::query()
            ->whereRelation('record_type', 'key', 'children_nth')
            ->whereIn('voucher_id', $vouchersQuery->select('id'))
            ->sum('value');
    }

    /**
     * @param Organization $organization
     * @return Collection|Arrayable
     */
    private function getFunds(Organization $organization): Collection|Arrayable
    {
        return $organization->funds()->where('archived', false)->where(
            'state',
            '!=',
            Fund::STATE_WAITING
        )->get();
    }

    /**
     * @param Organization $organization
     * @return Collection|Arrayable
     */
    private function getBudgetFunds(Organization $organization): Collection|Arrayable
    {
        return $this->getFunds($organization)->where('type', Fund::TYPE_BUDGET);
    }

    /**
     * @param Collection $funds
     * @param Carbon $from
     * @param Carbon $to
     * @return array
     */
    private function getFundTotals(Collection $funds, Carbon $from, Carbon $to): array
    {
        $budget = 0;
        $budget_left = 0;
        $budget_used = 0;
        $budget_used_active_vouchers = 0;
        $transaction_costs = 0;

        $query = Voucher::query()->whereNull('parent_id')->whereIn('fund_id', $funds->pluck('id'));
        $vouchersQuery = FinancialOverviewStatisticQueries::whereDate($query, $from, $to);
        $activeVouchersQuery = FinancialOverviewStatisticQueries::whereNotExpiredAndActive((clone $vouchersQuery));
        $inactiveVouchersQuery = FinancialOverviewStatisticQueries::wherePending((clone $vouchersQuery));
        $deactivatedVouchersQuery = FinancialOverviewStatisticQueries::whereDeactivated((clone $vouchersQuery));

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
            $total = FinancialOverviewStatisticQueries::getFundBudgetTotal($fund, $from, $to);
            $used = FinancialOverviewStatisticQueries::getFundBudgetUsed($fund, $from, $to);
            $left = round($total - $used, 2);

            $budget += $total;
            $budget_left += $left;
            $budget_used += $used;
            $budget_used_active_vouchers += FinancialOverviewStatisticQueries::getBudgetFundUsedActiveVouchers($fund, $from, $to);
            $transaction_costs += FinancialOverviewStatisticQueries::getFundTransactionCosts($fund, $from, $to);
        }

        $budget_locale = currency_format_locale($budget);
        $budget_left_locale = currency_format_locale($budget_left);
        $budget_used_locale = currency_format_locale($budget_used);
        $budget_used_active_vouchers_locale = currency_format_locale($budget_used_active_vouchers);
        $transaction_costs_locale = currency_format_locale($transaction_costs);

        return compact(
            'budget',
            'budget_left',
            'budget_used',
            'budget_used_active_vouchers',
            'transaction_costs',
            'vouchers_amount',
            'vouchers_count',
            'active_vouchers_amount',
            'active_vouchers_count',
            'inactive_vouchers_amount',
            'inactive_vouchers_count',
            'deactivated_vouchers_amount',
            'deactivated_vouchers_count',
            'vouchers_amount_locale',
            'active_vouchers_amount_locale',
            'inactive_vouchers_amount_locale',
            'deactivated_vouchers_amount_locale',
            'budget_locale',
            'budget_left_locale',
            'budget_used_locale',
            'budget_used_active_vouchers_locale',
            'transaction_costs_locale',
        );
    }
}
