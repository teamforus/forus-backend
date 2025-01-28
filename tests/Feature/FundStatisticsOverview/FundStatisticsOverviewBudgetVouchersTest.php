<?php

namespace Feature\FundStatisticsOverview;

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
use Throwable;

class FundStatisticsOverviewBudgetVouchersTest extends TestCase
{
    use WithFaker;
    use DoesTesting;
    use MakesTestFunds;
    use CreatesApplication;
    use MakesTestIdentities;
    use DatabaseTransactions;
    use MakesTestOrganizations;

    public int $fundTopUpAmount = 40000;

    /**
     * @return void
     * @throws Throwable
     */
    public function testFundStatisticsOverviewActiveVouchers(): void
    {
        $this->travelTo('2021-01-01');
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->createFundAndTopUpBudget($organization, $this->fundTopUpAmount);
        $this->assertActiveVouchers($fund, 1, 3, 100);

        $this->travelTo('2022-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update(['end_date' => now()->endOfYear()]);
        $this->assertActiveVouchers($fund, 2, 4, 50);

        $this->travelTo('2023-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update(['end_date' => now()->endOfYear()]);
        $this->assertActiveVouchers($fund, 5, 6, 70);

        $this->travelTo('2024-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update(['end_date' => now()->endOfYear()]);
        $this->assertActiveVouchers($fund, 12, 0, 120);
    }

    /**
     * @param Fund $fund
     * @param int $vouchersCount
     * @param int $childrenCount
     * @param int|float $transactionAmount
     * @return void
     */
    public function assertActiveVouchers(
        Fund $fund,
        int $vouchersCount,
        int $childrenCount,
        int|float $transactionAmount,
    ): void {
        $vouchers = $this->makeVouchers($fund, $vouchersCount, $childrenCount);
        $vouchersAmount = $vouchers->sum('amount');

        $childrenCount = $childrenCount * $vouchersCount;
        $usedAmount = $transactionAmount * $vouchersCount;
        $usedVouchersAmount = $transactionAmount * $vouchersCount;

        $employee = $fund->organization->employees[0];

        $vouchers->each(function (Voucher $voucher) use ($employee, $transactionAmount) {
            $voucher
                ->makeTransactionBySponsor($employee, ['amount' => $transactionAmount])
                ->setPaid(null, now());
        });

        $response = $this->getJson(
            "/api/v1/platform/organizations/$fund->organization_id/funds?stats=all&year=" . now()->year,
            $this->makeApiHeaders($fund->organization->identity),
        );

        $response->assertSuccessful();
        $response->assertJsonPath('data.0.budget.vouchers_count', $vouchersCount);
        $response->assertJsonPath('data.0.budget.active_vouchers_count', $vouchersCount);
        $response->assertJsonPath('data.0.budget.active_vouchers_amount', currency_format($vouchersAmount));
        $response->assertJsonPath('data.0.budget.deactivated_vouchers_count', 0);
        $response->assertJsonPath('data.0.budget.deactivated_vouchers_amount', currency_format(0));
        $response->assertJsonPath('data.0.budget.inactive_vouchers_count', 0);
        $response->assertJsonPath('data.0.budget.inactive_vouchers_amount', currency_format(0));

        $response->assertJsonPath('data.0.budget.total', currency_format($this->fundTopUpAmount));
        $response->assertJsonPath('data.0.budget.used', currency_format($usedAmount));
        $response->assertJsonPath('data.0.budget.left', currency_format($this->fundTopUpAmount - $usedAmount));
        $response->assertJsonPath('data.0.budget.used_active_vouchers', currency_format($usedVouchersAmount));
        $response->assertJsonPath('data.0.budget.children_count', $childrenCount);
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function testFundStatisticsOverviewPendingVouchers(): void
    {
        $this->travelTo('2021-01-01');
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->createFundAndTopUpBudget($organization, $this->fundTopUpAmount);
        $this->assertPendingVouchers($fund, 1, 2);

        $this->travelTo('2022-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update(['end_date' => now()->endOfYear()]);
        $this->assertPendingVouchers($fund, 2, 3);

        $this->travelTo('2023-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update(['end_date' => now()->endOfYear()]);
        $this->assertPendingVouchers($fund, 5, 1);

        $this->travelTo('2024-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update(['end_date' => now()->endOfYear()]);
        $this->assertPendingVouchers($fund, 12, 0);
    }

    /**
     * @param Fund $fund
     * @param int $vouchersCount
     * @param int $childrenCount
     * @return void
     */
    public function assertPendingVouchers(Fund $fund, int $vouchersCount, int $childrenCount): void
    {
        $vouchers = $this->makeVouchers($fund, $vouchersCount, $childrenCount);
        $vouchers->each(fn(Voucher $voucher) => $voucher->setPending());
        $vouchersAmount = $vouchers->sum('amount');
        $childrenCount = $childrenCount * $vouchersCount;

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
        $response->assertJsonPath('data.0.budget.inactive_vouchers_count', $vouchersCount);
        $response->assertJsonPath('data.0.budget.inactive_vouchers_amount', currency_format($vouchersAmount));

        $response->assertJsonPath('data.0.budget.total', currency_format($this->fundTopUpAmount));
        $response->assertJsonPath('data.0.budget.used', currency_format(0));
        $response->assertJsonPath('data.0.budget.left', currency_format($this->fundTopUpAmount));
        $response->assertJsonPath('data.0.budget.used_active_vouchers', currency_format(0));
        $response->assertJsonPath('data.0.budget.children_count', $childrenCount);
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function testFundStatisticsOverviewDeactivatedVouchers(): void
    {
        $this->travelTo('2021-01-01');
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->createFundAndTopUpBudget($organization, $this->fundTopUpAmount);
        $this->assertDeactivatedVouchers($fund, 1, 2, 100);

        $this->travelTo('2022-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update(['end_date' => now()->endOfYear()]);
        $this->assertDeactivatedVouchers($fund, 2, 5, 50);

        $this->travelTo('2023-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update(['end_date' => now()->endOfYear()]);
        $this->assertDeactivatedVouchers($fund, 5, 12, 70);

        $this->travelTo('2024-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update(['end_date' => now()->endOfYear()]);
        $this->assertDeactivatedVouchers($fund, 12, 0, 120);
    }

    /**
     * @param Fund $fund
     * @param int $vouchersCount
     * @param int $childrenCount
     * @param int|float $transactionAmount
     * @return void
     */
    public function assertDeactivatedVouchers(
        Fund $fund,
        int $vouchersCount,
        int $childrenCount,
        int|float $transactionAmount,
    ): void {
        $vouchers = $this->makeVouchers($fund, $vouchersCount, $childrenCount);
        $vouchersAmount = $vouchers->sum('amount');

        $childrenCount = $childrenCount * $vouchersCount;
        $usedAmount = $transactionAmount * $vouchersCount;
        $usedVouchersAmount = $transactionAmount * $vouchersCount;

        $employee = $fund->organization->employees[0];

        $vouchers->each(function (Voucher $voucher) use ($employee, $transactionAmount) {
            $voucher
                ->makeTransactionBySponsor($employee, ['amount' => $transactionAmount])
                ->setPaid(null, now());

            $voucher->deactivate();
        });

        $response = $this->getJson(
            "/api/v1/platform/organizations/$fund->organization_id/funds?stats=all&year=" . now()->year,
            $this->makeApiHeaders($fund->organization->identity),
        );

        $response->assertSuccessful();
        $response->assertJsonPath('data.0.budget.vouchers_count', $vouchersCount);
        $response->assertJsonPath('data.0.budget.active_vouchers_count', 0);
        $response->assertJsonPath('data.0.budget.active_vouchers_amount', currency_format(0));
        $response->assertJsonPath('data.0.budget.deactivated_vouchers_count', $vouchersCount);
        $response->assertJsonPath('data.0.budget.deactivated_vouchers_amount', currency_format($vouchersAmount));
        $response->assertJsonPath('data.0.budget.inactive_vouchers_count', 0);
        $response->assertJsonPath('data.0.budget.inactive_vouchers_amount', currency_format(0));

        $response->assertJsonPath('data.0.budget.total', currency_format($this->fundTopUpAmount));
        $response->assertJsonPath('data.0.budget.used', currency_format($usedAmount));
        $response->assertJsonPath('data.0.budget.left', currency_format($this->fundTopUpAmount - $usedAmount));
        $response->assertJsonPath('data.0.budget.used_active_vouchers', currency_format($usedVouchersAmount));
        $response->assertJsonPath('data.0.budget.children_count', $childrenCount);
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function testFundStatisticsOverviewExpiredVouchers(): void
    {
        $this->travelTo('2021-01-01');
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->createFundAndTopUpBudget($organization, $this->fundTopUpAmount);
        $this->assertExpiredVouchers($fund, 1, 4, 100);

        $this->travelTo('2022-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update(['end_date' => now()->endOfYear()]);
        $this->assertExpiredVouchers($fund, 2, 2, 50);

        $this->travelTo('2023-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update(['end_date' => now()->endOfYear()]);
        $this->assertExpiredVouchers($fund, 5, 12, 70);

        $this->travelTo('2024-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
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
     * @return Collection
     */
    private function makeVouchers(Fund $fund, int $count, int $childrenCount): Collection
    {
        $vouchers = collect();

        for ($i = 1; $i <= $count; $i++) {
            $voucher = $fund->makeVoucher($this->makeIdentity());
            $voucher->appendRecord('children_nth', $childrenCount);
            $vouchers->push($voucher);
        }

        return $vouchers;
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
        $response->assertJsonPath('budget_funds.vouchers_count', $vouchersCount);
        $response->assertJsonPath('budget_funds.vouchers_amount', currency_format($vouchersAmount));
        $response->assertJsonPath('budget_funds.active_vouchers_count', $vouchersCount);
        $response->assertJsonPath('budget_funds.active_vouchers_amount', currency_format($vouchersAmount));
        $response->assertJsonPath('budget_funds.deactivated_vouchers_count', 0);
        $response->assertJsonPath('budget_funds.deactivated_vouchers_amount', currency_format(0));
        $response->assertJsonPath('budget_funds.inactive_vouchers_count', 0);
        $response->assertJsonPath('budget_funds.inactive_vouchers_amount', currency_format(0));
        $response->assertJsonPath('budget_funds.budget_used_active_vouchers', $usedVouchersAmount);
    }
}