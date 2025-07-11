<?php

namespace Tests\Feature\FundStatisticsOverview;

use App\Models\Fund;
use App\Models\Organization;
use App\Models\Voucher;
use App\Traits\DoesTesting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Collection;
use Tests\CreatesApplication;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestIdentities;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestVouchers;
use Throwable;

class FundStatisticsOverviewBudgetVouchersTest extends TestCase
{
    use WithFaker;
    use DoesTesting;
    use MakesTestFunds;
    use MakesTestVouchers;
    use CreatesApplication;
    use MakesTestIdentities;
    use DatabaseTransactions;
    use MakesTestOrganizations;

    public int $fundTopUpAmount = 40000;

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundStatisticsOverviewActiveVouchers(): void
    {
        // 2021
        $this->travelTo('2021-01-01');
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->createFundAndTopUpBudget($organization, $this->fundTopUpAmount);

        $this->createActiveVouchers($fund, 1, 3, 25);
        $this->assertTotalAndActiveVouchersForYear($fund, 1, 1, 3, 25, 2021);

        // 2022
        $this->travelTo('2022-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update(['start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear()]);

        $this->createActiveVouchers($fund, 2, 4, 50);
        $this->assertTotalAndActiveVouchersForYear($fund, 1, 0, 3, 25, 2021);
        $this->assertTotalAndActiveVouchersForYear($fund, 2, 2, 8, 100, 2022);

        // 2023
        $this->travelTo('2023-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update(['start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear()]);

        $this->createActiveVouchers($fund, 5, 6, 70);
        $this->assertTotalAndActiveVouchersForYear($fund, 1, 0, 3, 25, 2021);
        $this->assertTotalAndActiveVouchersForYear($fund, 2, 0, 8, 100, 2022);
        $this->assertTotalAndActiveVouchersForYear($fund, 5, 5, 30, 350, 2023);

        // 2024
        $this->travelTo('2024-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update(['start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear()]);

        $this->createActiveVouchers($fund, 12, 0, 120);
        $this->assertTotalAndActiveVouchersForYear($fund, 1, 0, 3, 25, 2021);
        $this->assertTotalAndActiveVouchersForYear($fund, 2, 0, 8, 100, 2022);
        $this->assertTotalAndActiveVouchersForYear($fund, 5, 0, 30, 350, 2023);
        $this->assertTotalAndActiveVouchersForYear($fund, 12, 12, 0, 1440, 2024);
    }

    /**
     * @param Fund $fund
     * @param int $vouchersCount
     * @param int $childrenCount
     * @param int|float $transactionAmount
     * @return void
     */
    public function createActiveVouchers(
        Fund $fund,
        int $vouchersCount,
        int $childrenCount,
        int|float $transactionAmount,
    ): void {
        $this
            ->makeVouchers($fund, $vouchersCount, $childrenCount)
            ->each(fn (Voucher $voucher) => $voucher->makeTransactionBySponsor($fund->organization->employees[0], [
                'amount' => $transactionAmount,
            ])->setPaid(null, now()));
    }

    /**
     * @param Fund $fund
     * @param int $vouchersCount
     * @param int $vouchersCountActive
     * @param int $totalChildrenCount
     * @param int|float $totalTransactionsAmount
     * @param int $year
     * @return void
     */
    public function assertTotalAndActiveVouchersForYear(
        Fund $fund,
        int $vouchersCount,
        int $vouchersCountActive,
        int $totalChildrenCount,
        int|float $totalTransactionsAmount,
        int $year,
    ): void {
        $response = $this->getJson(
            "/api/v1/platform/organizations/$fund->organization_id/funds?stats=all&year=$year",
            $this->makeApiHeaders($fund->organization->identity),
        );

        $response->assertSuccessful();
        $response->assertJsonPath('data.0.budget.vouchers_count', $vouchersCount);
        $response->assertJsonPath('data.0.budget.active_vouchers_count', $vouchersCountActive);
        $response->assertJsonPath('data.0.budget.active_vouchers_amount', currency_format($vouchersCountActive * 300));
        $response->assertJsonPath('data.0.budget.deactivated_vouchers_count', 0);
        $response->assertJsonPath('data.0.budget.deactivated_vouchers_amount', currency_format(0));
        $response->assertJsonPath('data.0.budget.inactive_vouchers_count', 0);
        $response->assertJsonPath('data.0.budget.inactive_vouchers_amount', currency_format(0));

        $response->assertJsonPath('data.0.budget.total', currency_format($this->fundTopUpAmount));
        $response->assertJsonPath('data.0.budget.used', currency_format($totalTransactionsAmount));
        $response->assertJsonPath('data.0.budget.left', currency_format($this->fundTopUpAmount - $totalTransactionsAmount));
        $response->assertJsonPath('data.0.budget.used_active_vouchers', currency_format($totalTransactionsAmount));
        $response->assertJsonPath('data.0.budget.children_count', $totalChildrenCount);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundStatisticsOverviewPendingVouchers(): void
    {
        // 2021
        $this->travelTo('2021-01-01');
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->createFundAndTopUpBudget($organization, $this->fundTopUpAmount);

        $this->createPendingVouchers($fund, 1, 2);
        $this->assertPendingVouchersForYear($fund, 1, 2, 300, 2021);

        // 2022
        $this->travelTo('2022-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update(['start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear()]);

        $this->createPendingVouchers($fund, 2, 3);
        $this->assertPendingVouchersForYear($fund, 1, 2, 300, 2021);
        $this->assertPendingVouchersForYear($fund, 2, 6, 600, 2022);

        // 2023
        $this->travelTo('2023-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update(['start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear()]);

        $this->createPendingVouchers($fund, 5, 1);
        $this->assertPendingVouchersForYear($fund, 1, 2, 300, 2021);
        $this->assertPendingVouchersForYear($fund, 2, 6, 600, 2022);
        $this->assertPendingVouchersForYear($fund, 5, 5, 1500, 2023);

        // 2024
        $this->travelTo('2024-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update(['start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear()]);

        $this->createPendingVouchers($fund, 12, 0);
        $this->assertPendingVouchersForYear($fund, 1, 2, 300, 2021);
        $this->assertPendingVouchersForYear($fund, 2, 6, 600, 2022);
        $this->assertPendingVouchersForYear($fund, 5, 5, 1500, 2023);
        $this->assertPendingVouchersForYear($fund, 12, 0, 3600, 2024);
    }

    /**
     * @param Fund $fund
     * @param int $vouchersCount
     * @param int $childrenCount
     * @return void
     */
    public function createPendingVouchers(
        Fund $fund,
        int $vouchersCount,
        int $childrenCount
    ): void {
        $this
            ->makeVouchers($fund, $vouchersCount, $childrenCount)
            ->each(fn (Voucher $voucher) => $voucher->setPending());
    }

    /**
     * @param Fund $fund
     * @param int $vouchersCount
     * @param int $childrenCount
     * @param float $vouchersAmount
     * @param int $year
     * @return void
     */
    public function assertPendingVouchersForYear(
        Fund $fund,
        int $vouchersCount,
        int $childrenCount,
        float $vouchersAmount,
        int $year,
    ): void {
        $response = $this->getJson(
            "/api/v1/platform/organizations/$fund->organization_id/funds?stats=all&year=$year",
            $this->makeApiHeaders($fund->organization->identity),
        );

        $response->assertSuccessful();
        $response->assertJsonPath('data.0.budget.vouchers_count', $vouchersCount);
        $response->assertJsonPath('data.0.budget.active_vouchers_count', 0);
        $response->assertJsonPath('data.0.budget.active_vouchers_amount', currency_format(0));
        $response->assertJsonPath('data.0.budget.deactivated_vouchers_count', 0);
        $response->assertJsonPath('data.0.budget.deactivated_vouchers_amount', currency_format(0));
        $response->assertJsonPath('data.0.budget.inactive_vouchers_count', $vouchersCount);
        $response->assertJsonPath('data.0.budget.inactive_vouchers_amount', currency_format($vouchersAmount));

        $response->assertJsonPath('data.0.budget.total', currency_format($this->fundTopUpAmount));
        $response->assertJsonPath('data.0.budget.used', currency_format(0));
        $response->assertJsonPath('data.0.budget.left', currency_format($this->fundTopUpAmount));
        $response->assertJsonPath('data.0.budget.used_active_vouchers', currency_format(0));
        $response->assertJsonPath('data.0.budget.children_count', $childrenCount);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundStatisticsOverviewDeactivatedVouchers(): void
    {
        // 2021
        $this->travelTo('2021-01-01');
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->createFundAndTopUpBudget($organization, $this->fundTopUpAmount);

        $this->createDeactivatedVouchers($fund, 1, 2, 100);
        $this->assertDeactivatedVouchersForYear($fund, 1, 2, 100, 2021);

        // 2022
        $this->travelTo('2022-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update(['start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear()]);

        $this->createDeactivatedVouchers($fund, 2, 5, 50);
        $this->assertDeactivatedVouchersForYear($fund, 1, 2, 100, 2021);
        $this->assertDeactivatedVouchersForYear($fund, 2, 10, 100, 2022);

        // 2023
        $this->travelTo('2023-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update(['start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear()]);

        $this->createDeactivatedVouchers($fund, 5, 12, 70);
        $this->assertDeactivatedVouchersForYear($fund, 1, 2, 100, 2021);
        $this->assertDeactivatedVouchersForYear($fund, 2, 10, 100, 2022);
        $this->assertDeactivatedVouchersForYear($fund, 5, 60, 350, 2023);

        // 2024
        $this->travelTo('2024-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update(['start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear()]);

        $this->createDeactivatedVouchers($fund, 12, 0, 120);
        $this->assertDeactivatedVouchersForYear($fund, 1, 2, 100, 2021);
        $this->assertDeactivatedVouchersForYear($fund, 2, 10, 100, 2022);
        $this->assertDeactivatedVouchersForYear($fund, 5, 60, 350, 2023);
        $this->assertDeactivatedVouchersForYear($fund, 12, 0, 1440, 2024);
    }

    /**
     * @param Fund $fund
     * @param int $vouchersCount
     * @param int $childrenCount
     * @param float $transactionAmount
     * @throws Throwable
     * @return void
     */
    public function createDeactivatedVouchers(
        Fund $fund,
        int $vouchersCount,
        int $childrenCount,
        float $transactionAmount,
    ): void {
        $vouchers = $this->makeVouchers($fund, $vouchersCount, $childrenCount);
        $employee = $fund->organization->employees[0];

        $vouchers->each(function (Voucher $voucher) use ($employee, $transactionAmount) {
            $voucher
                ->makeTransactionBySponsor($employee, ['amount' => $transactionAmount])
                ->setPaid(null, now());

            $voucher->deactivate();
        });
    }

    /**
     * @param Fund $fund
     * @param int $vouchersCount
     * @param int $totalChildrenCount
     * @param float $totalVouchersAmount
     * @param int $year
     * @return void
     */
    public function assertDeactivatedVouchersForYear(
        Fund $fund,
        int $vouchersCount,
        int $totalChildrenCount,
        float $totalVouchersAmount,
        int $year,
    ): void {
        $response = $this->getJson(
            "/api/v1/platform/organizations/$fund->organization_id/funds?stats=all&year=$year",
            $this->makeApiHeaders($fund->organization->identity),
        );

        $response->assertSuccessful();
        $response->assertJsonPath('data.0.budget.vouchers_count', $vouchersCount);
        $response->assertJsonPath('data.0.budget.active_vouchers_count', 0);
        $response->assertJsonPath('data.0.budget.active_vouchers_amount', currency_format(0));
        $response->assertJsonPath('data.0.budget.deactivated_vouchers_count', $vouchersCount);
        $response->assertJsonPath('data.0.budget.deactivated_vouchers_amount', currency_format($vouchersCount * 300));
        $response->assertJsonPath('data.0.budget.inactive_vouchers_count', 0);
        $response->assertJsonPath('data.0.budget.inactive_vouchers_amount', currency_format(0));

        $response->assertJsonPath('data.0.budget.total', currency_format($this->fundTopUpAmount));
        $response->assertJsonPath('data.0.budget.used', currency_format($totalVouchersAmount));
        $response->assertJsonPath('data.0.budget.left', currency_format($this->fundTopUpAmount - $totalVouchersAmount));
        $response->assertJsonPath('data.0.budget.used_active_vouchers', currency_format($totalVouchersAmount));
        $response->assertJsonPath('data.0.budget.children_count', $totalChildrenCount);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundStatisticsOverviewExpiredVouchers(): void
    {
        $this->travelTo('2021-01-01');
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->createFundAndTopUpBudget($organization, $this->fundTopUpAmount);
        $this->assertExpiredVouchers($fund, 1, 4, 100);

        $this->travelTo('2022-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update(['start_date' => now()->startOfYear()]);
        $fund->update(['end_date' => now()->endOfYear()]);
        $this->assertExpiredVouchers($fund, 2, 2, 50);

        $this->travelTo('2023-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update(['start_date' => now()->startOfYear()]);
        $fund->update(['end_date' => now()->endOfYear()]);
        $this->assertExpiredVouchers($fund, 5, 12, 70);

        $this->travelTo('2024-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update(['start_date' => now()->startOfYear()]);
        $fund->update(['end_date' => now()->endOfYear()]);
        $this->assertExpiredVouchers($fund, 12, 0, 120);
    }

    /**
     * @param Fund $fund
     * @param int $vouchersCount
     * @param int $childrenCount
     * @param int|float $transactionAmount
     * @return void
     */
    public function assertExpiredVouchers(
        Fund $fund,
        int $vouchersCount,
        int $childrenCount,
        int|float $transactionAmount,
    ): void {
        $vouchers = $this->makeVouchers($fund, $vouchersCount, $childrenCount);
        $employee = $fund->organization->employees[0];

        $childrenCount = $childrenCount * $vouchersCount;
        $usedAmount = $transactionAmount * $vouchersCount;

        $vouchers->each(function (Voucher $voucher) use ($employee, $transactionAmount) {
            $voucher
                ->makeTransactionBySponsor($employee, ['amount' => $transactionAmount])
                ->setPaid(null, now());

            $voucher->update(['expire_at' => now()->startOfYear()->subDays(10)]);
        });

        $response = $this->getJson(
            "/api/v1/platform/organizations/$fund->organization_id/funds?stats=all&year=" . now()->year,
            $this->makeApiHeaders($fund->organization->identity),
        );

        $response->assertSuccessful();
        $response->assertJsonPath('data.0.budget.vouchers_count', $vouchersCount);
        $response->assertJsonPath('data.0.budget.active_vouchers_count', 0);
        $response->assertJsonPath('data.0.budget.active_vouchers_amount', currency_format(0));
        $response->assertJsonPath('data.0.budget.deactivated_vouchers_count', 0);
        $response->assertJsonPath('data.0.budget.deactivated_vouchers_amount', currency_format(0));
        $response->assertJsonPath('data.0.budget.inactive_vouchers_count', 0);
        $response->assertJsonPath('data.0.budget.inactive_vouchers_amount', currency_format(0));

        $response->assertJsonPath('data.0.budget.total', currency_format($this->fundTopUpAmount));
        $response->assertJsonPath('data.0.budget.used', currency_format($usedAmount));
        $response->assertJsonPath('data.0.budget.left', currency_format($this->fundTopUpAmount - $usedAmount));
        $response->assertJsonPath('data.0.budget.used_active_vouchers', currency_format(0));
        $response->assertJsonPath('data.0.budget.children_count', $childrenCount);
    }

    /**
     * @return void
     */
    public function testBudgetFundsTotals(): void
    {
        $vouchersCount = 0;
        $vouchersAmount = 0;
        $usedVouchersAmount = 0;
        $organization = $this->makeTestOrganization($this->makeIdentity());

        foreach (range(1, 5) as $index) {
            $vouchersCount += $index;
            $transactionAmount = 100;

            $fund = $this->createFundAndTopUpBudget($organization, $this->fundTopUpAmount);
            $vouchers = $this->makeVouchers($fund, $index, 1);

            $vouchersAmount += $vouchers->sum('amount');
            $usedVouchersAmount += $transactionAmount * $index;
            $employee = $fund->organization->employees[0];

            $vouchers->each(function (Voucher $voucher) use ($employee, $transactionAmount) {
                $voucher
                    ->makeTransactionBySponsor($employee, ['amount' => $transactionAmount])
                    ->setPaid(null, now());
            });
        }

        $response = $this->getJson(
            "/api/v1/platform/organizations/$fund->organization_id/sponsor/finances-overview?stats=all&year=" . now()->year,
            $this->makeApiHeaders($fund->organization->identity),
        );

        $response->assertSuccessful();
        $response->assertJsonPath('funds.vouchers_count', $vouchersCount);
        $response->assertJsonPath('funds.vouchers_amount', currency_format($vouchersAmount));
        $response->assertJsonPath('funds.active_vouchers_count', $vouchersCount);
        $response->assertJsonPath('funds.active_vouchers_amount', currency_format($vouchersAmount));
        $response->assertJsonPath('funds.deactivated_vouchers_count', 0);
        $response->assertJsonPath('funds.deactivated_vouchers_amount', currency_format(0));
        $response->assertJsonPath('funds.inactive_vouchers_count', 0);
        $response->assertJsonPath('funds.inactive_vouchers_amount', currency_format(0));
        $response->assertJsonPath('funds.budget_used_active_vouchers', $usedVouchersAmount);
    }

    /**
     * @param Organization $organization
     * @param float|int $topUpAmount
     * @return Fund
     */
    private function createFundAndTopUpBudget(Organization $organization, float|int $topUpAmount): Fund
    {
        $fund = $this->makeTestFund($organization, [
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
        ]);

        $fund->top_ups()->forceDelete();
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $topUpAmount]);

        return $fund;
    }

    /**
     * @param Fund $fund
     * @param int $count
     * @param int $childrenCount
     * @param int $amount
     * @return Collection
     */
    private function makeVouchers(Fund $fund, int $count, int $childrenCount, int $amount = 300): Collection
    {
        $vouchers = collect();

        for ($i = 1; $i <= $count; $i++) {
            $voucher = $this->makeTestVoucher($fund, $this->makeIdentity(), amount: $amount);
            $voucher->appendRecord('children_nth', $childrenCount);
            $vouchers->push($voucher);
        }

        return $vouchers;
    }
}
