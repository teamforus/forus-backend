<?php

namespace Feature\FundStatisticsOverview;

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
     * @return void
     * @throws Throwable
     */
    public function testFundStatisticsOverviewActiveProductVouchers(): void
    {
        $this->travelTo('2021-01-01');
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->createFundAndTopUpBudget($organization, $this->fundTopUpAmount);
        $this->assertActiveProductVouchers($fund, 1, 3);

        $this->travelTo('2022-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update(['end_date' => now()->endOfYear()]);
        $this->assertActiveProductVouchers($fund, 2, 5);

        $this->travelTo('2023-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update(['end_date' => now()->endOfYear()]);
        $this->assertActiveProductVouchers($fund, 5, 10);

        $this->travelTo('2024-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update(['end_date' => now()->endOfYear()]);
        $this->assertActiveProductVouchers($fund, 7, 0);
    }

    /**
     * @param Fund $fund
     * @param int $vouchersCount
     * @param int $childrenCount
     * @return void
     */
    public function assertActiveProductVouchers(
        Fund $fund,
        int $vouchersCount,
        int $childrenCount
    ): void {
        $vouchers = $this->makeProductVouchers($fund, $vouchersCount, $childrenCount);
        $childrenCount = $childrenCount * $vouchersCount;
        $vouchersAmount = $vouchers->sum('amount');
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
        });

        $response = $this->getJson(
            "/api/v1/platform/organizations/$fund->organization_id/funds?stats=all&year=" . now()->year,
            $this->makeApiHeaders($fund->organization->identity),
        );

        $response->assertSuccessful();
        $response->assertJsonPath('data.0.product_vouchers.vouchers_count', $vouchersCount);
        $response->assertJsonPath('data.0.product_vouchers.vouchers_amount', currency_format($vouchersAmount));
        $response->assertJsonPath('data.0.product_vouchers.active_vouchers_count', $vouchersCount);
        $response->assertJsonPath('data.0.product_vouchers.active_vouchers_amount', currency_format($vouchersAmount));
        $response->assertJsonPath('data.0.product_vouchers.deactivated_vouchers_count', 0);
        $response->assertJsonPath('data.0.product_vouchers.deactivated_vouchers_amount', currency_format(0));
        $response->assertJsonPath('data.0.product_vouchers.inactive_vouchers_count', 0);
        $response->assertJsonPath('data.0.product_vouchers.inactive_vouchers_amount', currency_format(0));
        $response->assertJsonPath('data.0.product_vouchers.children_count', $childrenCount);
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function testFundStatisticsOverviewPendingProductVouchers(): void
    {
        $this->travelTo('2021-01-01');
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->createFundAndTopUpBudget($organization, $this->fundTopUpAmount);
        $this->assertPendingProductVouchers($fund, 1, 3);

        $this->travelTo('2022-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update(['end_date' => now()->endOfYear()]);
        $this->assertPendingProductVouchers($fund, 2, 5);

        $this->travelTo('2023-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update(['end_date' => now()->endOfYear()]);
        $this->assertPendingProductVouchers($fund, 5, 12);

        $this->travelTo('2024-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update(['end_date' => now()->endOfYear()]);
        $this->assertPendingProductVouchers($fund, 7, 0);
    }

    /**
     * @param Fund $fund
     * @param int $vouchersCount
     * @param int $childrenCount
     * @return void
     */
    public function assertPendingProductVouchers(
        Fund $fund,
        int $vouchersCount,
        int $childrenCount
    ): void {
        $vouchers = $this->makeProductVouchers($fund, $vouchersCount, $childrenCount);
        $childrenCount = $childrenCount * $vouchersCount;
        $vouchersAmount = $vouchers->sum('amount');
        $vouchers->each(fn (Voucher $voucher) => $voucher->setPending());

        $response = $this->getJson(
            "/api/v1/platform/organizations/$fund->organization_id/funds?stats=all&year=" . now()->year,
            $this->makeApiHeaders($fund->organization->identity),
        );

        $response->assertSuccessful();
        $response->assertJsonPath('data.0.product_vouchers.vouchers_count', $vouchersCount);
        $response->assertJsonPath('data.0.product_vouchers.vouchers_amount', currency_format($vouchersAmount));
        $response->assertJsonPath('data.0.product_vouchers.active_vouchers_count', 0);
        $response->assertJsonPath('data.0.product_vouchers.active_vouchers_amount', currency_format(0));
        $response->assertJsonPath('data.0.product_vouchers.deactivated_vouchers_count', 0);
        $response->assertJsonPath('data.0.product_vouchers.deactivated_vouchers_amount', currency_format(0));
        $response->assertJsonPath('data.0.product_vouchers.inactive_vouchers_count', $vouchersCount);
        $response->assertJsonPath('data.0.product_vouchers.inactive_vouchers_amount', currency_format($vouchersAmount));
        $response->assertJsonPath('data.0.product_vouchers.children_count', $childrenCount);
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function testFundStatisticsOverviewDeactivatedProductVouchers(): void
    {
        $this->travelTo('2021-01-01');
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->createFundAndTopUpBudget($organization, $this->fundTopUpAmount);
        $this->assertDeactivatedProductVouchers($fund, 1, 3);

        $this->travelTo('2022-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update(['end_date' => now()->endOfYear()]);
        $this->assertDeactivatedProductVouchers($fund, 2, 5);

        $this->travelTo('2023-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update(['end_date' => now()->endOfYear()]);
        $this->assertDeactivatedProductVouchers($fund, 5, 12);

        $this->travelTo('2024-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update(['end_date' => now()->endOfYear()]);
        $this->assertDeactivatedProductVouchers($fund, 7, 0);
    }

    /**
     * @param Fund $fund
     * @param int $vouchersCount
     * @param int $childrenCount
     * @return void
     */
    public function assertDeactivatedProductVouchers(
        Fund $fund,
        int $vouchersCount,
        int $childrenCount
    ): void {
        $vouchers = $this->makeProductVouchers($fund, $vouchersCount, $childrenCount);
        $childrenCount = $childrenCount * $vouchersCount;
        $vouchersAmount = $vouchers->sum('amount');
        $vouchers->each(fn (Voucher $voucher) => $voucher->deactivate());

        $response = $this->getJson(
            "/api/v1/platform/organizations/$fund->organization_id/funds?stats=all&year=" . now()->year,
            $this->makeApiHeaders($fund->organization->identity),
        );

        $response->assertSuccessful();
        $response->assertJsonPath('data.0.product_vouchers.vouchers_count', $vouchersCount);
        $response->assertJsonPath('data.0.product_vouchers.vouchers_amount', currency_format($vouchersAmount));
        $response->assertJsonPath('data.0.product_vouchers.active_vouchers_count', 0);
        $response->assertJsonPath('data.0.product_vouchers.active_vouchers_amount', currency_format(0));
        $response->assertJsonPath('data.0.product_vouchers.deactivated_vouchers_count', $vouchersCount);
        $response->assertJsonPath('data.0.product_vouchers.deactivated_vouchers_amount', currency_format($vouchersAmount));
        $response->assertJsonPath('data.0.product_vouchers.inactive_vouchers_count', 0);
        $response->assertJsonPath('data.0.product_vouchers.inactive_vouchers_amount', currency_format(0));
        $response->assertJsonPath('data.0.product_vouchers.children_count', $childrenCount);
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function testFundStatisticsOverviewExpiredProductVouchers(): void
    {
        $this->travelTo('2021-01-01');
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->createFundAndTopUpBudget($organization, $this->fundTopUpAmount);
        $this->assertExpiredProductVouchers($fund, 1, 3);

        $this->travelTo('2022-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update(['end_date' => now()->endOfYear()]);
        $this->assertExpiredProductVouchers($fund, 2, 5);

        $this->travelTo('2023-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update(['end_date' => now()->endOfYear()]);
        $this->assertExpiredProductVouchers($fund, 5, 12);

        $this->travelTo('2024-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update(['end_date' => now()->endOfYear()]);
        $this->assertExpiredProductVouchers($fund, 7, 0);
    }

    /**
     * @param Fund $fund
     * @param int $vouchersCount
     * @param int $childrenCount
     * @return void
     */
    public function assertExpiredProductVouchers(
        Fund $fund,
        int $vouchersCount,
        int $childrenCount
    ): void {
        $vouchers = $this->makeProductVouchers($fund, $vouchersCount, $childrenCount);
        $childrenCount = $childrenCount * $vouchersCount;
        $vouchersAmount = $vouchers->sum('amount');
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

        $response = $this->getJson(
            "/api/v1/platform/organizations/$fund->organization_id/funds?stats=all&year=" . now()->year,
            $this->makeApiHeaders($fund->organization->identity),
        );

        $response->assertSuccessful();
        $response->assertJsonPath('data.0.product_vouchers.vouchers_count', $vouchersCount);
        $response->assertJsonPath('data.0.product_vouchers.vouchers_amount', currency_format($vouchersAmount));
        $response->assertJsonPath('data.0.product_vouchers.active_vouchers_count', 0);
        $response->assertJsonPath('data.0.product_vouchers.active_vouchers_amount', currency_format(0));
        $response->assertJsonPath('data.0.product_vouchers.deactivated_vouchers_count', 0);
        $response->assertJsonPath('data.0.product_vouchers.deactivated_vouchers_amount', currency_format(0));
        $response->assertJsonPath('data.0.product_vouchers.inactive_vouchers_count', 0);
        $response->assertJsonPath('data.0.product_vouchers.inactive_vouchers_amount', currency_format(0));
        $response->assertJsonPath('data.0.product_vouchers.children_count', $childrenCount);
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
    private function makeProductVouchers(Fund $fund, int $count, int $childrenCount): Collection
    {
        $vouchers = collect();
        $products = $this->makeProductsFundFund($count);

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