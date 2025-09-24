<?php

namespace Tests\Browser\Filters\Webshop;

use App\Models\BusinessType;
use App\Models\Fund;
use App\Models\Organization;
use App\Models\ProductCategory;
use App\Models\Tag;
use Facebook\WebDriver\Exception\ElementClickInterceptedException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeOutException;
use Illuminate\Support\Facades\Config;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Assert;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\NavigatesFrontendWebshop;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizationOffices;
use Tests\Traits\MakesTestReimbursements;

abstract class BaseWebshopSearchFilter extends DuskTestCase
{
    use MakesTestFunds;
    use HasFrontendActions;
    use RollbackModelsTrait;
    use MakesTestFundRequests;
    use MakesTestReimbursements;
    use NavigatesFrontendWebshop;
    use MakesProductReservations;
    use MakesTestOrganizationOffices;

    /**
     * @return string
     */
    abstract public function getListSelector(): string;

    /**
     * @param Browser $browser
     * @param int $id
     * @param bool $visible
     * @param int|null $totalRows
     * @param string|null $listSelector
     * @throws TimeoutException
     * @return void
     */
    protected function assertListVisibility(
        Browser $browser,
        int $id,
        bool $visible,
        int $totalRows = null,
        string $listSelector = null,
    ): void {
        $listSelector = $listSelector ?: $this->getListSelector();

        if ($visible) {
            $browser->waitFor($listSelector . "Row$id");
            $browser->assertVisible($listSelector . "Row$id");

        } else {
            $browser->waitUntilMissing($listSelector . "Row$id");
            $browser->assertMissing($listSelector . "Row$id");
        }

        if ($totalRows !== null) {
            $this->assertListCount($browser, $totalRows, $listSelector);
        }
    }

    /**
     * @param Browser $browser
     * @param int $totalRows
     * @param string|null $listSelector
     * @throws TimeoutException
     * @return void
     */
    protected function assertListCount(
        Browser $browser,
        int $totalRows,
        string $listSelector = null,
    ): void {
        $listSelector = $listSelector ?: $this->getListSelector();

        $attribute = Config::get('tests.dusk_selector');
        $duskPrefix = ltrim($listSelector, '@');
        $selector = "[$attribute^=\"{$duskPrefix}Row\"]";

        $browser->waitUsing(
            null,
            100,
            fn () => count($browser->elements($selector)) === $totalRows,
            "Timeout waiting for $totalRows rows with selector: $selector"
        );

        $rows = $browser->elements($selector);
        Assert::assertCount($totalRows, $rows, "Expected $totalRows rows, got " . count($rows));
    }

    /**
     * @param Browser $browser
     * @param Organization $provider
     * @param int $id
     * @param int $total
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeOutException
     * @return void
     */
    protected function assertListFilterByOrganization(Browser $browser, Organization $provider, int $id, int $total): void
    {
        $this->uncollapseFilterGroup($browser, '@productFiltersGroupProviders');
        $this->changeSelectControl($browser, '@selectControlOrganizations', text: $provider->name);
        $this->assertListVisibility($browser, $id, true);
        $this->assertWebshopRowsCount($browser, $total, $this->getListSelector() . 'Content');
        $this->changeSelectControl($browser, '@selectControlOrganizations', index: 0);
    }

    /**
     * @param Browser $browser
     * @param Tag $tag
     * @param int $id
     * @param int $total
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeOutException
     * @return void
     */
    protected function assertListFilterByTag(Browser $browser, Tag $tag, int $id, int $total): void
    {
        $this->changeSelectControl($browser, '@selectControlTags', text: $tag->name);
        $this->assertListVisibility($browser, $id, true);
        $this->assertWebshopRowsCount($browser, $total, $this->getListSelector() . 'Content');
        $this->changeSelectControl($browser, '@selectControlTags', index: 0);
    }

    /**
     * @param Browser $browser
     * @param int $id
     * @param ProductCategory $category
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    protected function assertListFilterByProductCategory(Browser $browser, int $id, ProductCategory $category): void
    {
        $this->uncollapseFilterGroup($browser, '@productFilterGroupProductCategories');
        $this->clearCategoryFilterItems($browser, '@productFilterGroupProductCategories');

        $baseCategory = $category->parent ?: $category;

        $this->assertListCount($browser, 0, $this->getListSelector() . 'Content');
        $browser->waitFor('@productCategoryFilterOption' . $baseCategory->id);
        $browser->click('@productCategoryFilterOption' . $baseCategory->id);

        $this->assertListVisibility($browser, $id, true);
        $this->assertWebshopRowsCount($browser, 1, $this->getListSelector() . 'Content');

        $this->clearCategoryFilterItems($browser, '@productFilterGroupProductCategories');
    }

    /**
     * @param Browser $browser
     * @param string $selector
     * @return void
     */
    protected function clearCategoryFilterItems(Browser $browser, string $selector): void
    {
        $browser->within($selector, function (Browser $browser) {
            if ($browser->elements('.category-filter-item-active')) {
                $browser->click('.category-filter-item-active');
            }

            $browser->waitUntilMissing('.category-filter-item-active');
        });
    }

    /**
     * @param Browser $browser
     * @param Fund $fund
     * @param int $id
     * @param int|null $total
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     */
    protected function assertListFilterByFund(Browser $browser, Fund $fund, int $id, int $total = null): void
    {
        $this->uncollapseFilterGroup($browser, '@productFilterGroupFunds');
        $browser->waitFor('@productFilterFundItem' . $fund->id);
        $browser->click('@productFilterFundItem' . $fund->id);
        $this->assertListVisibility($browser, $id, true, $total);

        $this->assertWebshopRowsCount($browser, $total, $this->getListSelector() . 'Content');

        $browser->waitFor('@productFilterFundItem' . $fund->id);
        $browser->click('@productFilterFundItem' . $fund->id);
    }

    /**
     * @param Browser $browser
     * @param BusinessType $businessType
     * @param int $id
     * @param int $total
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeOutException
     * @return void
     */
    protected function assertListFilterByBusinessType(Browser $browser, BusinessType $businessType, int $id, int $total): void
    {
        $this->uncollapseFilterGroup($browser, '@productFilterGroupBusinessTypes');
        $this->changeSelectControl($browser, '@selectControlBusinessTypes', text: $businessType->name);
        $this->assertListVisibility($browser, $id, true);
        $this->assertWebshopRowsCount($browser, $total, $this->getListSelector() . 'Content');
        $this->changeSelectControl($browser, '@selectControlBusinessTypes', index: 0);
    }

    /**
     * @param Browser $browser
     * @param string $selector
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @return void
     */
    protected function uncollapseFilterGroup(Browser $browser, string $selector): void
    {
        if (!str_contains($browser->element($selector)?->getAttribute('class') ?: '', 'showcase-aside-group-open')) {
            $browser->click($selector . ' .showcase-aside-group-title-toggle');
        }
    }

    /**
     * @param Browser $browser
     * @param string $state
     * @param int $id
     * @param int $total
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeOutException
     * @return void
     */
    protected function assertListFilterByState(Browser $browser, string $state, int $id, int $total): void
    {
        $this->changeSelectControl($browser, '@selectControlStates', text: $state);
        $this->assertListVisibility($browser, $id, true);
        $this->assertWebshopRowsCount($browser, $total, $this->getListSelector() . 'Content');
        $this->changeSelectControl($browser, '@selectControlStates', index: 0);
    }

    /**
     * @param Browser $browser
     * @param string $postCode
     * @param int $id
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     */
    protected function assertListFilterByDistance(Browser $browser, string $postCode, int $id): void
    {
        $this->uncollapseFilterGroup($browser, '@productFilterGroupDistance');
        $browser->waitFor('@inputPostcode');
        $browser->typeSlowly('@inputPostcode', $postCode, 0);

        $this->changeSelectControl($browser, '@selectControlDistances', text: '< 5 km');
        $this->assertListVisibility($browser, $id, true);

        $browser->clear('@inputPostcode');
        $this->changeSelectControl($browser, '@selectControlDistances', index: 0);
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return void
     */
    protected function fillListSearchForEmptyResults(Browser $browser): void
    {
        $this->searchWebshopList($browser, $this->getListSelector(), '########', null, 0);
        $this->clearField($browser, $this->getListSelector() . 'Search');
    }

    /**
     * @param Browser $browser
     * @param string $text
     * @param int $id
     * @param int $total
     * @throws TimeoutException
     * @return void
     */
    protected function assertListFilterQueryValue(Browser $browser, string $text, int $id, int $total = 1): void
    {
        $listSelector = $this->getListSelector();

        $this->searchWebshopList($browser, $listSelector, $text, $id, $total);
        $this->clearField($browser, $listSelector . 'Search');
        $this->fillListSearchForEmptyResults($browser);
    }
}
