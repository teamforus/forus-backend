<?php

namespace Tests\Browser\Filters\Webshop;

use App\Models\Fund;
use App\Models\Implementation;
use Facebook\WebDriver\Exception\TimeOutException;
use Laravel\Dusk\Browser;
use Throwable;

class FundsWebshopSearchFilterTest extends BaseWebshopSearchFilter
{
    /**
     * @return string
     */
    public function getListSelector(): string
    {
        return '@listFunds';
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundsFilter(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $fund = $this->makeTestFund($this->makeTestOrganization($this->makeIdentity()), implementation: $implementation);
        $fund2 = $this->makeTestFund($this->makeTestOrganization($this->makeIdentity()), implementation: $implementation);
        $tag = $this->makeAndAppendTestFundTag($fund);

        $this->rollbackModels([], function () use ($fund, $tag) {
            $this->browse(function (Browser $browser) use ($fund, $tag) {
                $browser->visit($fund->urlWebshop('fondsen'));

                $this->fillListSearchForEmptyResults($browser);
                $this->assertFundsSearchIsWorking($browser, $fund);

                $this->fillListSearchForEmptyResults($browser);
                $this->assertListFilterByOrganizationsCheckboxes($browser, $fund->id, $fund->organization);

                $this->fillListSearchForEmptyResults($browser);
                $this->assertListFilterByTagsCheckboxes($browser, $fund->id, $tag);
            });
        }, function () use ($fund, $fund2) {
            $fund && $this->deleteFund($fund);
            $fund2 && $this->deleteFund($fund2);
        });
    }

    /**
     * @param Browser $browser
     * @param Fund $fund
     * @throws TimeOutException
     * @return void
     */
    protected function assertFundsSearchIsWorking(Browser $browser, Fund $fund): void
    {
        $this->assertListFilterQueryValue($browser, $fund->name, $fund->id);
        $this->assertListFilterQueryValue($browser, $fund->organization->name, $fund->id);
        $this->assertListFilterQueryValue($browser, $fund->description_text, $fund->id);
        $this->assertListFilterQueryValue($browser, $fund->description_short, $fund->id);
    }
}
