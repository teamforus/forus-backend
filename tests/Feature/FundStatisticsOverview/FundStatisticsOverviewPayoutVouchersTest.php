<?php

namespace Tests\Feature\FundStatisticsOverview;

use App\Models\Data\BankAccount;
use App\Models\Fund;
use App\Models\FundConfig;
use App\Models\Organization;
use App\Traits\DoesTesting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\CreatesApplication;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestIdentities;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class FundStatisticsOverviewPayoutVouchersTest extends TestCase
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
    public function testFundStatisticsOverviewPayoutVouchers(): void
    {
        // 2021
        $this->travelTo('2021-01-01');
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->createFundAndTopUpBudget($organization, $this->fundTopUpAmount);

        $this->createPayouts($fund, 1);
        $this->assertTotalAndActivePayoutVouchersForYear($fund, 1, 2021);

        // 2022
        $this->travelTo('2022-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update([ 'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear() ]);

        $this->createPayouts($fund, 4);
        $this->assertTotalAndActivePayoutVouchersForYear($fund, 1, 2021);
        $this->assertTotalAndActivePayoutVouchersForYear($fund, 4, 2022);

        // 2023
        $this->travelTo('2023-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update(['start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear()]);

        $this->createPayouts($fund, 3);
        $this->assertTotalAndActivePayoutVouchersForYear($fund, 1, 2021);
        $this->assertTotalAndActivePayoutVouchersForYear($fund, 4, 2022);
        $this->assertTotalAndActivePayoutVouchersForYear($fund, 3, 2023);

        // 2024
        $this->travelTo('2024-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $this->fundTopUpAmount]);
        $fund->update([ 'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear() ]);

        $this->createPayouts($fund, 6);
        $this->assertTotalAndActivePayoutVouchersForYear($fund, 1, 2021);
        $this->assertTotalAndActivePayoutVouchersForYear($fund, 4, 2022);
        $this->assertTotalAndActivePayoutVouchersForYear($fund, 3, 2023);
        $this->assertTotalAndActivePayoutVouchersForYear($fund, 6, 2024);
    }

    /**
     * @param Fund $fund
     * @param int $count
     * @return void
     */
    private function createPayouts(Fund $fund, int $count): void
    {
        $employee = $fund->organization->employees[0];

        for ($i = 1; $i <= $count; $i++) {
            $identity = $this->makeIdentity();

            $fund->makePayout(
                identity: $identity,
                amount: 100,
                employee: $employee,
                bankAccount: new BankAccount(
                    $this->faker()->iban(),
                    $this->faker()->name(),
                ),
            )->setPaid(null, now());
        }
    }

    /**
     * Assert the yearly payout vouchers statistics for active vouchers.
     *
     * @param Fund $fund
     * @param int $vouchersCount Total vouchers count for the year.
     * @param int $year
     * @return void
     */
    private function assertTotalAndActivePayoutVouchersForYear(
        Fund $fund,
        int $vouchersCount,
        int $year,
    ): void {
        $response = $this->getJson(
            "/api/v1/platform/organizations/$fund->organization_id/funds?stats=all&year=$year",
            $this->makeApiHeaders($fund->organization->identity),
        );

        $response->assertSuccessful();
        $response->assertJsonPath('data.0.payout_vouchers.vouchers_count', $vouchersCount);
        $response->assertJsonPath('data.0.payout_vouchers.vouchers_amount', currency_format($vouchersCount * 100));
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
        ], [
            'outcome_type' => FundConfig::OUTCOME_TYPE_PAYOUT,
        ]);

        $fund->top_ups()->forceDelete();
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $topUpAmount]);

        return $fund;
    }
}
