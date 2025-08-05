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
        $tag = $this->makeAndAppendTestFundTag($fund);

        $this->rollbackModels([], function () use ($fund, $tag) {
            $this->browse(function (Browser $browser) use ($fund, $tag) {
                $browser->visit($fund->urlWebshop('fondsen'));

                $this->fillListSearchForEmptyResults($browser);
                $this->assertFundsSearchIsWorking($browser, $fund);

                $this->fillListSearchForEmptyResults($browser);
                $this->assertListFilterByOrganization($browser, $fund->organization, $fund->id, 1);

                $this->fillListSearchForEmptyResults($browser);
                $this->assertListFilterByTag($browser, $tag, $fund->id, 1);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
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
