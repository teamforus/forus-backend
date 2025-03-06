<?php

namespace Tests\Feature\FundStatisticsOverview;

use App\Models\Fund;
use App\Models\Organization;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
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

class FundStatisticsOverviewProductVouchersTest extends TestCase
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
     * Test active product vouchers over multiple years.
     *
     * @throws Throwable
     * @return void
     */
    public function testFundStatisticsOverviewActiveProductVouchers(): void
    {
        // 2021
        $this->travelTo('2021-01-01');
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->createFundAndTopUpBudget($organization, $this->fundTopUpAmount);

        $this->createActiveProductVouchers($fund, 1, 3);
        $this->assertTotalAndActiveProductVouchersForYear($fund, 1, 1, 3, 2021);

        // 2022
        $this->travelTo('2022-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update([ 'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear() ]);

        $this->createActiveProductVouchers($fund, 2, 5);
        $this->assertTotalAndActiveProductVouchersForYear($fund, 1, 0, 3, 2021);
        $this->assertTotalAndActiveProductVouchersForYear($fund, 2, 2, 10, 2022);

        // 2023
        $this->travelTo('2023-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update(['start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear()]);

        $this->createActiveProductVouchers($fund, 5, 10);
        $this->assertTotalAndActiveProductVouchersForYear($fund, 1, 0, 3, 2021);
        $this->assertTotalAndActiveProductVouchersForYear($fund, 2, 0, 10, 2022);
        $this->assertTotalAndActiveProductVouchersForYear($fund, 5, 5, 50, 2023);

        // 2024
        $this->travelTo('2024-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update([ 'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear() ]);

        $this->createActiveProductVouchers($fund, 7, 0);
        $this->assertTotalAndActiveProductVouchersForYear($fund, 1, 0, 3, 2021);
        $this->assertTotalAndActiveProductVouchersForYear($fund, 2, 0, 10, 2022);
        $this->assertTotalAndActiveProductVouchersForYear($fund, 5, 0, 50, 2023);
        $this->assertTotalAndActiveProductVouchersForYear($fund, 7, 7, 0, 2024);
    }

    /**
     * Create active product vouchers.
     *
     * @param Fund $fund
     * @param int $vouchersCount
     * @param int $childrenCount
     * @return void
     */
    public function createActiveProductVouchers(
        Fund $fund,
        int $vouchersCount,
        int $childrenCount,
    ): void {
        $this
            ->makeProductVouchers($fund, $vouchersCount, $childrenCount)
            ->each(function (Voucher $voucher) use ($fund) {
                $employee = $fund->organization->employees[0];
                $params = [
                    'amount' => $voucher->amount,
                    'product_id' => $voucher->product_id,
                    'employee_id' => $employee?->id,
                    'branch_id' => $employee?->office?->branch_id,
                    'branch_name' => $employee?->office?->branch_name,
                    'branch_number' => $employee?->office?->branch_number,
                    'target' => VoucherTransaction::TARGET_PROVIDER,
                    'organization_id' => $voucher->product->organization_id,
                ];
                $voucher->makeTransaction($params)->setPaid(null, now());
            });
    }

    /**
     * Assert the yearly product vouchers statistics for active vouchers.
     *
     * @param Fund $fund
     * @param int $vouchersCount Total vouchers count for the year.
     * @param int $vouchersCountActive Active vouchers count.
     * @param int $totalChildrenCount Total children count.
     * @param int $year
     * @return void
     */
    public function assertTotalAndActiveProductVouchersForYear(
        Fund $fund,
        int $vouchersCount,
        int $vouchersCountActive,
        int $totalChildrenCount,
        int $year,
    ): void {
        $response = $this->getJson(
            "/api/v1/platform/organizations/$fund->organization_id/funds?stats=all&year=$year",
            $this->makeApiHeaders($fund->organization->identity),
        );

        $response->assertSuccessful();
        $response->assertJsonPath('data.0.product_vouchers.vouchers_count', $vouchersCount);
        $response->assertJsonPath('data.0.product_vouchers.active_vouchers_count', $vouchersCountActive);
        $response->assertJsonPath('data.0.product_vouchers.active_vouchers_amount', currency_format($vouchersCountActive * 5));
        $response->assertJsonPath('data.0.product_vouchers.deactivated_vouchers_count', 0);
        $response->assertJsonPath('data.0.product_vouchers.deactivated_vouchers_amount', currency_format(0));
        $response->assertJsonPath('data.0.product_vouchers.inactive_vouchers_count', 0);
        $response->assertJsonPath('data.0.product_vouchers.inactive_vouchers_amount', currency_format(0));
        $response->assertJsonPath('data.0.product_vouchers.children_count', $totalChildrenCount);
    }

    /**
     * Test pending product vouchers over multiple years.
     *
     * @throws Throwable
     * @return void
     */
    public function testFundStatisticsOverviewPendingProductVouchers(): void
    {
        // 2021
        $this->travelTo('2021-01-01');
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->createFundAndTopUpBudget($organization, $this->fundTopUpAmount);

        $this->createPendingProductVouchers($fund, 1, 3);
        $this->assertPendingProductVouchersForYear($fund, 1, 3, 5, 2021);

        // 2022
        $this->travelTo('2022-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update([ 'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear() ]);

        $this->createPendingProductVouchers($fund, 2, 5);
        $this->assertPendingProductVouchersForYear($fund, 1, 3, 5, 2021);
        $this->assertPendingProductVouchersForYear($fund, 2, 10, 10, 2022);

        // 2023
        $this->travelTo('2023-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update([ 'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear() ]);

        $this->createPendingProductVouchers($fund, 5, 12);
        $this->assertPendingProductVouchersForYear($fund, 1, 3, 5, 2021);
        $this->assertPendingProductVouchersForYear($fund, 2, 10, 10, 2022);
        $this->assertPendingProductVouchersForYear($fund, 5, 60, 25, 2023);

        // 2024
        $this->travelTo('2024-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update([ 'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear() ]);

        $this->createPendingProductVouchers($fund, 7, 0);
        $this->assertPendingProductVouchersForYear($fund, 1, 3, 5, 2021);
        $this->assertPendingProductVouchersForYear($fund, 2, 10, 10, 2022);
        $this->assertPendingProductVouchersForYear($fund, 5, 60, 25, 2023);
        $this->assertPendingProductVouchersForYear($fund, 7, 0, 35, 2024);
    }

    /**
     * Create pending product vouchers.
     *
     * @param Fund $fund
     * @param int $vouchersCount
     * @param int $childrenCount
     * @return void
     */
    public function createPendingProductVouchers(Fund $fund, int $vouchersCount, int $childrenCount): void
    {
        $this
            ->makeProductVouchers($fund, $vouchersCount, $childrenCount)
            ->each(fn (Voucher $voucher) => $voucher->setPending());
    }

    /**
     * Assert the yearly product vouchers statistics for pending vouchers.
     *
     * @param Fund $fund
     * @param int $vouchersCount
     * @param int $childrenCountTotal Total children count (vouchersCount * childrenCount)
     * @param float $vouchersAmountTotal Total vouchers amount.
     * @param int $year
     * @return void
     */
    public function assertPendingProductVouchersForYear(
        Fund $fund,
        int $vouchersCount,
        int $childrenCountTotal,
        float $vouchersAmountTotal,
        int $year,
    ): void {
        $response = $this->getJson(
            "/api/v1/platform/organizations/$fund->organization_id/funds?stats=all&year=$year",
            $this->makeApiHeaders($fund->organization->identity)
        );

        $response->assertSuccessful();
        $response->assertJsonPath('data.0.product_vouchers.vouchers_count', $vouchersCount);
        $response->assertJsonPath('data.0.product_vouchers.vouchers_amount', currency_format($vouchersAmountTotal));
        $response->assertJsonPath('data.0.product_vouchers.active_vouchers_count', 0);
        $response->assertJsonPath('data.0.product_vouchers.active_vouchers_amount', currency_format(0));
        $response->assertJsonPath('data.0.product_vouchers.deactivated_vouchers_count', 0);
        $response->assertJsonPath('data.0.product_vouchers.deactivated_vouchers_amount', currency_format(0));
        $response->assertJsonPath('data.0.product_vouchers.inactive_vouchers_count', $vouchersCount);
        $response->assertJsonPath('data.0.product_vouchers.inactive_vouchers_amount', currency_format($vouchersAmountTotal));
        $response->assertJsonPath('data.0.product_vouchers.children_count', $childrenCountTotal);
    }

    /**
     * Test deactivated product vouchers over multiple years.
     *
     * @throws Throwable
     * @return void
     */
    public function testFundStatisticsOverviewDeactivatedProductVouchers(): void
    {
        // 2021
        $this->travelTo('2021-01-01');
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->createFundAndTopUpBudget($organization, $this->fundTopUpAmount);

        $this->createDeactivatedProductVouchers($fund, 1, 3);
        $this->assertDeactivatedProductVouchersForYear($fund, 1, 3, 5, 2021);

        // 2022
        $this->travelTo('2022-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update([ 'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear() ]);

        $this->createDeactivatedProductVouchers($fund, 2, 5);
        $this->assertDeactivatedProductVouchersForYear($fund, 1, 3, 5, 2021);
        $this->assertDeactivatedProductVouchersForYear($fund, 2, 10, 10, 2022);

        // 2023
        $this->travelTo('2023-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update([ 'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear() ]);

        $this->createDeactivatedProductVouchers($fund, 5, 12);
        $this->assertDeactivatedProductVouchersForYear($fund, 1, 3, 5, 2021);
        $this->assertDeactivatedProductVouchersForYear($fund, 2, 10, 10, 2022);
        $this->assertDeactivatedProductVouchersForYear($fund, 5, 60, 25, 2023);

        // 2024
        $this->travelTo('2024-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update([ 'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear() ]);

        $this->createDeactivatedProductVouchers($fund, 7, 0);
        $this->assertDeactivatedProductVouchersForYear($fund, 1, 3, 5, 2021);
        $this->assertDeactivatedProductVouchersForYear($fund, 2, 10, 10, 2022);
        $this->assertDeactivatedProductVouchersForYear($fund, 5, 60, 25, 2023);
        $this->assertDeactivatedProductVouchersForYear($fund, 7, 0, 35, 2024);
    }

    /**
     * Create deactivated product vouchers.
     *
     * @param Fund $fund
     * @param int $vouchersCount
     * @param int $childrenCount
     * @return void
     * @throws Throwable
     */
    public function createDeactivatedProductVouchers(
        Fund $fund,
        int $vouchersCount,
        int $childrenCount,
    ): void {
        $vouchers = $this->makeProductVouchers($fund, $vouchersCount, $childrenCount);
        $employee = $fund->organization->employees[0];

        $vouchers->each(function (Voucher $voucher) use ($employee) {
            $voucher->makeTransaction([
                'amount' => $voucher->amount,
                'product_id' => $voucher->product_id,
                'employee_id' => $employee?->id,
                'branch_id' => $employee?->office?->branch_id,
                'branch_name' => $employee?->office?->branch_name,
                'branch_number' => $employee?->office?->branch_number,
                'target' => VoucherTransaction::TARGET_PROVIDER,
                'organization_id' => $voucher->product->organization_id,
            ])->setPaid(null, now());

            $voucher->deactivate();
        });
    }

    /**
     * Assert the yearly product vouchers statistics for deactivated vouchers.
     *
     * @param Fund $fund
     * @param int $vouchersCount
     * @param int $totalChildrenCount
     * @param float $totalVoucherAmount
     * @param int $year
     * @return void
     */
    public function assertDeactivatedProductVouchersForYear(
        Fund $fund,
        int $vouchersCount,
        int $totalChildrenCount,
        float $totalVoucherAmount,
        int $year
    ): void {
        $response = $this->getJson(
            "/api/v1/platform/organizations/$fund->organization_id/funds?stats=all&year=$year",
            $this->makeApiHeaders($fund->organization->identity)
        );

        $response->assertSuccessful();
        $response->assertJsonPath('data.0.product_vouchers.vouchers_count', $vouchersCount);
        $response->assertJsonPath('data.0.product_vouchers.vouchers_amount', currency_format($totalVoucherAmount));
        $response->assertJsonPath('data.0.product_vouchers.active_vouchers_count', 0);
        $response->assertJsonPath('data.0.product_vouchers.active_vouchers_amount', currency_format(0));
        $response->assertJsonPath('data.0.product_vouchers.deactivated_vouchers_count', $vouchersCount);
        $response->assertJsonPath('data.0.product_vouchers.deactivated_vouchers_amount', currency_format($totalVoucherAmount));
        $response->assertJsonPath('data.0.product_vouchers.inactive_vouchers_count', 0);
        $response->assertJsonPath('data.0.product_vouchers.inactive_vouchers_amount', currency_format(0));
        $response->assertJsonPath('data.0.product_vouchers.children_count', $totalChildrenCount);
    }

    /**
     * Test expired product vouchers over multiple years.
     *
     * @throws Throwable
     * @return void
     */
    public function testFundStatisticsOverviewExpiredProductVouchers(): void
    {
        // 2021
        $this->travelTo('2021-01-01');
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->createFundAndTopUpBudget($organization, $this->fundTopUpAmount);

        $this->createExpiredProductVouchers($fund, 1, 3);
        $this->assertExpiredProductVouchersForYear($fund, 1, 3, 5, 2021);

        // 2022
        $this->travelTo('2022-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update([ 'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear() ]);
        $this->createExpiredProductVouchers($fund, 2, 5);
        $this->assertExpiredProductVouchersForYear($fund, 1, 3, 5, 2021);
        $this->assertExpiredProductVouchersForYear($fund, 2, 10, 10, 2022);

        // 2023
        $this->travelTo('2023-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update([ 'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear() ]);
        $this->createExpiredProductVouchers($fund, 5, 12);
        $this->assertExpiredProductVouchersForYear($fund, 1, 3, 5, 2021);
        $this->assertExpiredProductVouchersForYear($fund, 2, 10, 10, 2022);
        $this->assertExpiredProductVouchersForYear($fund, 5, 60, 25, 2023);

        // 2024
        $this->travelTo('2024-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update([ 'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear() ]);
        $this->createExpiredProductVouchers($fund, 7, 0);
        $this->assertExpiredProductVouchersForYear($fund, 1, 3, 5, 2021);
        $this->assertExpiredProductVouchersForYear($fund, 2, 10, 10, 2022);
        $this->assertExpiredProductVouchersForYear($fund, 5, 60, 25, 2023);
        $this->assertExpiredProductVouchersForYear($fund, 7, 0, 35, 2024);
    }

    /**
     * Create expired product vouchers.
     *
     * @param Fund $fund
     * @param int $vouchersCount
     * @param int $childrenCount
     * @return void
     */
    public function createExpiredProductVouchers(
        Fund $fund,
        int $vouchersCount,
        int $childrenCount,
    ): void {
        $vouchers = $this->makeProductVouchers($fund, $vouchersCount, $childrenCount);
        $employee = $fund->organization->employees[0];

        $vouchers->each(function (Voucher $voucher) use ($employee) {
            $params = [
                'amount' => $voucher->amount,
                'product_id' => $voucher->product_id,
                'employee_id' => $employee?->id,
                'branch_id' => $employee?->office?->branch_id,
                'branch_name' => $employee?->office?->branch_name,
                'branch_number' => $employee?->office?->branch_number,
                'target' => VoucherTransaction::TARGET_PROVIDER,
                'organization_id' => $voucher->product->organization_id,
            ];

            $voucher->makeTransaction($params)->setPaid(null, now());
            $voucher->update(['expire_at' => now()->startOfYear()->subDays(10)]);
        });
    }

    /**
     * Assert the yearly product vouchers statistics for expired vouchers.
     *
     * @param Fund $fund
     * @param int $vouchersCount
     * @param int $totalChildrenCount
     * @param float $totalVoucherAmount
     * @param int $year
     * @return void
     */
    public function assertExpiredProductVouchersForYear(
        Fund $fund,
        int $vouchersCount,
        int $totalChildrenCount,
        float $totalVoucherAmount,
        int $year
    ): void {
        $response = $this->getJson(
            "/api/v1/platform/organizations/$fund->organization_id/funds?stats=all&year=$year",
            $this->makeApiHeaders($fund->organization->identity),
        );

        $response->assertSuccessful();
        $response->assertJsonPath('data.0.product_vouchers.vouchers_count', $vouchersCount);
        $response->assertJsonPath('data.0.product_vouchers.vouchers_amount', currency_format($totalVoucherAmount));
        $response->assertJsonPath('data.0.product_vouchers.active_vouchers_count', 0);
        $response->assertJsonPath('data.0.product_vouchers.active_vouchers_amount', currency_format(0));
        $response->assertJsonPath('data.0.product_vouchers.deactivated_vouchers_count', 0);
        $response->assertJsonPath('data.0.product_vouchers.deactivated_vouchers_amount', currency_format(0));
        $response->assertJsonPath('data.0.product_vouchers.inactive_vouchers_count', 0);
        $response->assertJsonPath('data.0.product_vouchers.inactive_vouchers_amount', currency_format(0));
        $response->assertJsonPath('data.0.product_vouchers.children_count', $totalChildrenCount);
    }

    /**
     * Create fund and top-up its budget.
     *
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
     * Create product vouchers with an optional amount.
     *
     * @param Fund $fund
     * @param int $count
     * @param int $childrenCount
     * @return Collection
     */
    private function makeProductVouchers(Fund $fund, int $count, int $childrenCount): Collection
    {
        $vouchers = collect();
        $products = $this->makeProductsFundFund($count, 5);

        for ($i = 1; $i <= $count; $i++) {
            $product = $products[$i - 1];
            $this->addProductFundToFund($fund, $product, false);

            $voucher = $fund->makeProductVoucher($this->makeIdentity(), [], $product->id);
            $voucher->appendRecord('children_nth', $childrenCount);
            $vouchers->push($voucher);
        }

        return $vouchers;
    }
}
