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
use Throwable;

class FundRequestsWebshopSearchFilterTest extends BaseWebshopSearchFilter
{
    public function getListSelector(): string
    {
        return '@listFundRequests';
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundRequestsFilter(): void
    {
        $this->assertFundRequestFilters(function (Browser $browser, FundRequest $request1, FundRequest $request2) {
            $this->assertListFilterByFund($browser, $request1->fund, $request1->id, 1);
            $this->assertListFilterByFund($browser, $request2->fund, $request2->id, 1);
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundRequestsFilterByActiveTabs(): void
    {
        $this->assertFundRequestFilters(function (Browser $browser, FundRequest $request1, FundRequest $request2) {
            $request2->decline();
            $this->assertFundRequestsFilterByActiveTabs($browser, $request1, $request2);
        });
    }

    /**
     * @param callable $callback
     * @throws Throwable
     * @return void
     */
    protected function assertFundRequestFilters(callable $callback): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $implementation = Implementation::byKey('nijmegen');
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        $fund = $this->makeTestFund($organization, implementation: $implementation);
        $fund2 = $this->makeTestFund($organization, implementation: $implementation);

        $fundRequest = $this->makeFundRequestForIdentity($fund, $identity);
        $fundRequest2 = $this->makeFundRequestForIdentity($fund2, $identity);

        $this->rollbackModels([], function () use ($implementation, $identity, $callback, $fundRequest, $fundRequest2) {
            $this->browse(function (Browser $browser) use ($implementation, $identity, $callback, $fundRequest, $fundRequest2) {
                $browser->visit($implementation->urlWebshop());

                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnWebshop($browser, $identity);
                $this->goToIdentityFundRequests($browser);

                $callback($browser, $fundRequest, $fundRequest2);
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
        FundRequest $inactiveFundRequest,
    ): void {
        $browser->waitFor('@fundRequestsFilterActive');
        $browser->click('@fundRequestsFilterActive');
        $this->assertListFilterByFund($browser, $activeFundRequest->fund, $activeFundRequest->id, 1);

        $browser->click('@fundRequestsFilterArchived');
        $this->assertListFilterByFund($browser, $inactiveFundRequest->fund, $inactiveFundRequest->id, 1);
    }
}
