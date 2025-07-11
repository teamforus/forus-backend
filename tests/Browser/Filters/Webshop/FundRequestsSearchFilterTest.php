<?php

namespace Tests\Browser\Filters\Webshop;

use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\Identity;
use App\Models\Implementation;
use Facebook\WebDriver\Exception\ElementClickInterceptedException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeOutException;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Throwable;

class FundRequestsSearchFilterTest extends DuskTestCase
{
    use MakesTestFunds;
    use HasFrontendActions;
    use RollbackModelsTrait;
    use MakesTestFundRequests;

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundRequestsFilter(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $implementation = Implementation::byKey('nijmegen');
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        $fundConfigsData = [
            'implementation_id' => $implementation->id,
        ];

        $fund = $this->makeTestFund($organization, fundConfigsData: $fundConfigsData);
        $fund2 = $this->makeTestFund($organization, fundConfigsData: $fundConfigsData);

        $fundRequest = $this->makeFundRequestForIdentity($fund, $identity);
        $fundRequest2 = $this->makeFundRequestForIdentity($fund2, $identity);

        $this->rollbackModels([], function () use ($implementation, $identity, $fundRequest, $fundRequest2) {
            $this->browse(function (Browser $browser) use ($implementation, $identity, $fundRequest, $fundRequest2) {
                $browser->visit($implementation->urlWebshop());

                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnWebshop($browser, $identity);
                $this->goToIdentityFundRequests($browser);

                $this->assertFundRequestFilterByFund($browser, $fundRequest, $fundRequest2);
                $this->assertFundRequestFilterByFund($browser, $fundRequest2, $fundRequest);

                $this->logout($browser);
            });
        }, function () use ($organization) {
            $organization->funds->each(fn (Fund $fund) => $this->deleteFund($fund));
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundRequestsFilterByActiveTabs(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $implementation = Implementation::byKey('nijmegen');
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        $fundConfigsData = [
            'implementation_id' => $implementation->id,
        ];

        $fund = $this->makeTestFund($organization, fundConfigsData: $fundConfigsData);
        $fund2 = $this->makeTestFund($organization, fundConfigsData: $fundConfigsData);

        $activeFundRequest = $this->makeFundRequestForIdentity($fund, $identity);
        $declinedFundRequest = $this->makeFundRequestForIdentity($fund2, $identity)->decline();

        $this->rollbackModels([], function () use ($implementation, $identity, $activeFundRequest, $declinedFundRequest) {
            $this->browse(function (Browser $browser) use ($implementation, $identity, $activeFundRequest, $declinedFundRequest) {
                $browser->visit($implementation->urlWebshop());

                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnWebshop($browser, $identity);
                $this->goToIdentityFundRequests($browser);

                $this->assertFundRequestsFilterByActiveTabs($browser, $activeFundRequest, $declinedFundRequest);

                $this->logout($browser);
            });
        }, function () use ($organization) {
            $organization->funds->each(fn (Fund $fund) => $this->deleteFund($fund));
        });
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
     * @param FundRequest $fundRequest
     * @param FundRequest $fundRequestOtherFund
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeOutException
     * @return FundRequestsSearchFilterTest
     */
    protected function assertFundRequestFilterByFund(
        Browser $browser,
        FundRequest $fundRequest,
        FundRequest $fundRequestOtherFund,
    ): static {
        $browser->waitFor('@selectControlFunds');
        $browser->click('@selectControlFunds .select-control-search');
        $this->findOptionElement($browser, '@selectControlFunds', $fundRequest->fund->name)->click();

        $this
            ->assertFundRequestVisible($browser, $fundRequest)
            ->assertFundRequestNotVisible($browser, $fundRequestOtherFund);

        return $this;
    }

    /**
     * @param Browser $browser
     * @param FundRequest $activeFundRequest
     * @param FundRequest $inactiveFundRequest
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeOutException
     * @return void
     */
    protected function assertFundRequestsFilterByActiveTabs(
        Browser $browser,
        FundRequest $activeFundRequest,
        FundRequest $inactiveFundRequest
    ): void {
        $browser->waitFor('@fundRequestsFilterActive');
        $browser->click('@fundRequestsFilterActive');

        $this
            ->assertFundRequestVisible($browser, $activeFundRequest)
            ->assertFundRequestNotVisible($browser, $inactiveFundRequest);

        $browser->click('@fundRequestsFilterArchived');

        $this
            ->assertFundRequestVisible($browser, $inactiveFundRequest)
            ->assertFundRequestNotVisible($browser, $activeFundRequest);
    }

    /**
     * @param Browser $browser
     * @param FundRequest $fundRequest
     * @throws TimeoutException
     * @return FundRequestsSearchFilterTest
     */
    protected function assertFundRequestVisible(Browser $browser, FundRequest $fundRequest): static
    {
        $browser->waitFor("@listFundRequestsRow$fundRequest->id");
        $browser->assertVisible("@listFundRequestsRow$fundRequest->id");
        $this->assertWebshopRowsCount($browser, 1, '@listFundRequestsContent');

        return $this;
    }

    /**
     * @param Browser $browser
     * @param FundRequest $fundRequest
     * @throws TimeoutException
     * @return FundRequestsSearchFilterTest
     */
    protected function assertFundRequestNotVisible(Browser $browser, FundRequest $fundRequest): static
    {
        $browser->waitUntilMissing("@listFundRequestsRow$fundRequest->id");

        return $this;
    }
}
