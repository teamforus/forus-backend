<?php

namespace Tests\Browser\Filters\Webshop;

use App\Models\Fund;
use App\Models\Implementation;
use Facebook\WebDriver\Exception\ElementClickInterceptedException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeOutException;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFunds;
use Throwable;

class FundsSearchFilterTest extends DuskTestCase
{
    use MakesTestFunds;
    use HasFrontendActions;
    use RollbackModelsTrait;

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundsFilter(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $implementation = Implementation::general();

        $fund = $this->makeTestFund($organization, [
            'description_text' => $this->faker->sentence,
            'description_short' => $this->faker->sentence,
        ], [
            'implementation_id' => $implementation->id,
        ]);

        $tagName = $this->faker->name;

        $fund->tags()->firstOrCreate([
            'key' => Str::slug($tagName),
            'scope' => 'webshop',
        ])->translateOrNew(app()->getLocale())->fill([
            'name' => $tagName,
        ])->save();

        $this->rollbackModels([], function () use ($implementation, $fund, $tagName) {
            $this->browse(function (Browser $browser) use ($implementation, $fund, $tagName) {
                $browser->visit($implementation->urlWebshop('fondsen'));

                $this->assertFundsSearchIsWorking($browser, $fund)
                    ->fillSearchForEmptyResults($browser)
                    ->assertFundsSearchByOrganization($browser, $fund)
                    ->fillSearchForEmptyResults($browser)
                    ->assertFundsSearchByTag($browser, $fund, $tagName);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @param Browser $browser
     * @param Fund $fund
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return FundsSearchFilterTest
     */
    protected function assertFundsSearchByOrganization(Browser $browser, Fund $fund): static
    {
        $browser->waitFor('@selectControlOrganizations');
        $browser->click('@selectControlOrganizations .select-control-search');
        $this->findOptionElement($browser, '@selectControlOrganizations', $fund->organization->name)->click();

        return $this->assertFundVisible($browser, $fund);
    }

    /**
     * @param Browser $browser
     * @param Fund $fund
     * @param string $tagName
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return FundsSearchFilterTest
     */
    protected function assertFundsSearchByTag(Browser $browser, Fund $fund, string $tagName): static
    {
        $browser->waitFor('@selectControlTags');
        $browser->click('@selectControlTags .select-control-search');
        $this->findOptionElement($browser, '@selectControlTags', $tagName)->click();

        return $this->assertFundVisible($browser, $fund);
    }

    /**
     * @param Browser $browser
     * @param Fund $fund
     * @throws TimeOutException
     * @return FundsSearchFilterTest
     */
    protected function assertFundsSearchIsWorking(Browser $browser, Fund $fund): static
    {
        return $this
            ->assertSearch($browser, $fund, $fund->name)
            ->fillSearchForEmptyResults($browser)
            ->assertSearch($browser, $fund, $fund->organization->name)
            ->fillSearchForEmptyResults($browser)
            ->assertSearch($browser, $fund, $fund->description_text)
            ->fillSearchForEmptyResults($browser)
            ->assertSearch($browser, $fund, $fund->description_short)
            ->fillSearchForEmptyResults($browser);
    }

    /**
     * @param Browser $browser
     * @param Fund $fund
     * @param string $q
     * @throws TimeoutException
     * @return FundsSearchFilterTest
     */
    protected function assertSearch(Browser $browser, Fund $fund, string $q): static
    {
        $this->searchWebshopList($browser, '@listFunds', $q, $fund->id);
        $this->clearField($browser, '@listFundsSearch');

        return $this;
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return FundsSearchFilterTest
     */
    protected function fillSearchForEmptyResults(Browser $browser): static
    {
        $this->searchWebshopList($browser, '@listFunds', '###############', null, 0);
        $this->clearField($browser, '@listFundsSearch');

        return $this;
    }

    /**
     * @param Browser $browser
     * @param Fund $fund
     * @param int $count
     * @throws TimeoutException
     * @return FundsSearchFilterTest
     */
    protected function assertFundVisible(Browser $browser, Fund $fund, int $count = 1): static
    {
        $browser->waitFor("@listFundsRow$fund->id");
        $browser->assertVisible("@listFundsRow$fund->id");
        $this->assertWebshopRowsCount($browser, $count, '@listFundsContent');

        return $this;
    }
}
