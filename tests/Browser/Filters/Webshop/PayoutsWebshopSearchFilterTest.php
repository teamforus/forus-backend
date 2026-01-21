<?php

namespace Tests\Browser\Filters\Webshop;

use App\Models\Data\BankAccount;
use App\Models\Fund;
use App\Models\FundConfig;
use App\Models\FundRequest;
use App\Models\Identity;
use App\Models\Implementation;
use App\Models\VoucherTransaction;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\NavigatesFrontendWebshop;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Throwable;

class PayoutsWebshopSearchFilterTest extends BaseWebshopSearchFilter
{
    use MakesTestFunds;
    use HasFrontendActions;
    use NavigatesFrontendWebshop;
    use RollbackModelsTrait;
    use MakesTestFundRequests;

    public function getListSelector(): string
    {
        return '@listPayouts';
    }

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

        $fund = $this->makeTestFund($organization, fundConfigsData: $fundConfigsData, implementation: $implementation);
        $payout = $this->makePayout($fund, $identity);

        $fund2 = $this->makeTestFund($organization, fundConfigsData: $fundConfigsData, implementation: $implementation);
        $payout2 = $this->makePayout($fund2, $identity);

        $this->rollbackModels([], function () use ($implementation, $identity, $payout, $payout2) {
            $this->browse(function (Browser $browser) use ($implementation, $identity, $payout, $payout2) {
                $browser->visit($implementation->urlWebshop());

                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnWebshop($browser, $identity);
                $this->goToIdentityPayouts($browser);

                $this->assertListFilterQueryValue($browser, $payout->voucher->fund->name, $payout->id);
                $this->assertListFilterQueryValue($browser, (string) $payout->id, $payout->id);

                $this->assertListFilterQueryValue($browser, $payout2->voucher->fund->name, $payout2->id);
                $this->assertListFilterQueryValue($browser, (string) $payout2->id, $payout2->id);

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
            bankAccount: new BankAccount($this->faker()->iban(), $this->faker()->name()),
            voucherFields: [ 'fund_request_id' => $fundRequest->id ],
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
}
