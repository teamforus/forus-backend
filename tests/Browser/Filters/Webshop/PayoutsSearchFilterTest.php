<?php

namespace Tests\Browser\Filters\Webshop;

use App\Models\Data\BankAccount;
use App\Models\Fund;
use App\Models\FundConfig;
use App\Models\FundRequest;
use App\Models\Identity;
use App\Models\Implementation;
use App\Models\VoucherTransaction;
use Facebook\WebDriver\Exception\TimeOutException;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Throwable;

class PayoutsSearchFilterTest extends DuskTestCase
{
    use MakesTestFunds;
    use HasFrontendActions;
    use RollbackModelsTrait;
    use MakesTestFundRequests;

    /**
     * @throws Throwable
     * @return void
     */
    public function testPayoutsFilter(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;
        $organization->update(['allow_payouts' => true]);
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        $fundConfigsData = [
            'outcome_type' => FundConfig::OUTCOME_TYPE_PAYOUT,
            'iban_record_key' => 'iban',
            'iban_name_record_key' => 'iban_name',
        ];

        $fund = $this->makeTestFund($organization, fundConfigsData: $fundConfigsData);
        $payout = $this->makePayout($fund, $identity);

        $fund2 = $this->makeTestFund($organization, fundConfigsData: $fundConfigsData);
        $payout2 = $this->makePayout($fund2, $identity);

        $this->rollbackModels([], function () use ($implementation, $identity, $payout, $payout2) {
            $this->browse(function (Browser $browser) use ($implementation, $identity, $payout, $payout2) {
                $browser->visit($implementation->urlWebshop());

                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnWebshop($browser, $identity);
                $this->goToIdentityPayouts($browser);

                $this->assertSearch($browser, $payout, $payout->voucher->fund->name)
                    ->assertSearch($browser, $payout, (string) $payout->id);

                $this->assertSearch($browser, $payout2, $payout2->voucher->fund->name)
                    ->assertSearch($browser, $payout2, (string) $payout2->id);

                $this->logout($browser);
            });
        }, function () use ($fund, $fund2) {
            $this->deleteFund($fund);
            $this->deleteFund($fund2);
        });
    }

    /**
     * @param Fund $fund
     * @param Identity $identity
     * @return VoucherTransaction
     */
    protected function makePayout(Fund $fund, Identity $identity): VoucherTransaction
    {
        $employee = $fund->organization->employees[0];
        $fundRequest = $this->makeFundRequestForIdentity($fund, $identity);

        $payout = $fund->makePayout(
            identity: $identity,
            amount: 100,
            employee: $employee,
            bankAccount: new BankAccount(
                $this->faker()->iban,
                $this->faker()->name,
            ),
            voucherFields: [
                'fund_request_id' => $fundRequest->id,
            ],
        );

        $payout->setPaid(null, now());

        return $payout;
    }

    /**
     * @param Fund $fund
     * @param Identity $identity
     * @return FundRequest
     */
    protected function makeFundRequestForIdentity(Fund $fund, Identity $identity): FundRequest
    {
        $records = [[
            'fund_criterion_id' => $fund->criteria[0]?->id,
            'value' => 5,
            'files' => [],
        ]];

        $response = $this->makeFundRequest($identity, $fund, $records, false);
        $response->assertSuccessful();

        $fundRequest = FundRequest::find($response->json('data.id'));
        $this->assertNotNull($fundRequest);

        return $fundRequest;
    }

    /**
     * @param Browser $browser
     * @param VoucherTransaction $payout
     * @param string $q
     * @throws TimeoutException
     * @return PayoutsSearchFilterTest
     */
    protected function assertSearch(Browser $browser, VoucherTransaction $payout, string $q): static
    {
        $this->searchWebshopList($browser, '@listPayouts', $q, $payout->id);
        $this->clearField($browser, '@listPayoutsSearch');

        return $this;
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return PayoutsSearchFilterTest
     */
    protected function fillSearchForEmptyResults(Browser $browser): static
    {
        $this->searchWebshopList($browser, '@listPayouts', '###############', null, 0);
        $this->clearField($browser, '@listPayoutsSearch');

        return $this;
    }
}
