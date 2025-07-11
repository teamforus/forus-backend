<?php

namespace Tests\Browser\Filters\Webshop;

use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductCategory;
use Facebook\WebDriver\Exception\ElementClickInterceptedException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeOutException;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFunds;
use Throwable;

class MainSearchFilterTest extends DuskTestCase
{
    use MakesTestFunds;
    use HasFrontendActions;
    use RollbackModelsTrait;

    /**
     * @throws Throwable
     * @return void
     */
    public function testMainFilters(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $implementation = Implementation::byKey('nijmegen');

        $fund = $this->makeTestFund($organization, fundConfigsData: [
            'implementation_id' => $implementation->id,
        ]);

        $product = $this->prepareProduct($fund);

        $this->rollbackModels([], function () use ($implementation, $fund, $product) {
            $this->browse(function (Browser $browser) use ($implementation, $fund, $product) {
                $providerOrganization = $product->organization;

                $browser->visit($implementation->urlWebshop('search'));
                $browser->waitFor($this->getWebshopRowsSelector());

                $this->assertMainSearchFundIsWorking($browser, $fund)
                    ->assertMainSearchProviderIsWorking($browser, $providerOrganization)
                    ->assertMainSearchProductIsWorking($browser, $product);

                $this
                    ->toggleFilterTypeOptions($browser, 'providers')
                    ->assertFilterByProvider($browser, $providerOrganization)
                    ->clearProviderSelect($browser)
                    ->toggleFilterTypeOptions($browser, 'providers')
                    ->fillSearchForEmptyResults($browser);

                $this
                    ->toggleFilterTypeOptions($browser, 'products')
                    ->assertFilterByCategory($browser, $product)
                    ->clearCategorySelect($browser)
                    ->toggleFilterTypeOptions($browser, 'products')
                    ->fillSearchForEmptyResults($browser);

                $this
                    ->toggleFilterTypeOptions($browser, 'funds')
                    ->assertFilterByFund($browser, $fund)
                    ->clearFundSelect($browser)
                    ->toggleFilterTypeOptions($browser, 'funds');
            });
        }, function () use ($fund, $product) {
            $fund && $this->deleteFund($fund);
            $product->delete();
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProductsSorting(): void
    {
        $orderByColumns = [
            'created_at' => ['asc', 'desc'],
        ];

        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $implementation = Implementation::byKey('nijmegen');

        $fund = $this->makeTestFund($organization, fundConfigsData: [
            'implementation_id' => $implementation->id,
        ]);

        [$product, $product2] = $this->makeProducts($fund);

        $this->rollbackModels([], function () use ($fund, $product, $product2, $orderByColumns) {
            $this->browse(function (Browser $browser) use ($fund, $product, $product2, $orderByColumns) {
                $implementation = $fund->refresh()->getImplementation();
                $browser->visit($implementation->urlWebshop('search'))->refresh();
                $browser->waitFor($this->getWebshopRowsSelector());

                $this
                    ->toggleFilterTypeOptions($browser, 'products')
                    ->assertFilterByProvider($browser, $product->organization, 2);

                foreach ($orderByColumns as $item => $dirs) {
                    array_walk($dirs, fn ($dir) => $this->assertProductsSorting($browser, $product, $product2, $item, $dir));
                }
            });
        }, function () use ($fund, $product, $product2) {
            $fund && $this->deleteFund($fund);
            $product->delete();
            $product2->delete();
        });
    }

    /**
     * @param Fund $fund
     * @return Product[]
     */
    protected function makeProducts(Fund $fund): array
    {
        $products = $this->makeProductsFundFund(2);
        array_walk($products, fn (Product $product) => $this->addProductFundToFund($fund, $product, false));

        $product = $products[0];
        $product->created_at = $product->created_at->clone()->subDay();
        $product->save();

        return [$product, $products[1]];
    }

    /**
     * @param Browser $browser
     * @param Product $product
     * @param Product $product2
     * @param string $column
     * @param string $dir
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return MainSearchFilterTest
     */
    protected function assertProductsSorting(
        Browser $browser,
        Product $product,
        Product $product2,
        string $column,
        string $dir
    ): static {
        $name = match ($column) {
            'created_at' => $dir === 'asc' ? 'Oudste eerst' : 'Nieuwe eerst',
            default => throw new InvalidArgumentException("Unsupported order by column: $column"),
        };

        $browser->waitFor('@selectControlOrderBy');
        $browser->click('@selectControlOrderBy .select-control-search');
        $this->findOptionElement($browser, '@selectControlOrderBy', $name)->click();

        $this->assertProductVisible($browser, $product, 2);
        $this->assertProductVisible($browser, $product2, 2);

        $browser->waitUsing(5, 100, function () use ($browser, $dir, $product, $product2) {
            $elements = $browser->elements($this->getWebshopRowsSelector());

            if ($dir === 'asc') {
                return
                    str_contains($elements[0]->getText(), $product->name) &&
                    str_contains($elements[1]->getText(), $product2->name);
            }

            return
                str_contains($elements[0]->getText(), $product2->name) &&
                str_contains($elements[1]->getText(), $product->name);

        });

        return $this;
    }

    /**
     * @param Fund $fund
     * @return Product
     */
    protected function prepareProduct(Fund $fund): Product
    {
        $product = $this->makeProductsFundFund(1)[0];
        $this->addProductFundToFund($fund, $product, false);

        $product->update([
            'product_category_id' => $this->makeProductCategory()->id,
            'description_text' => $this->faker->sentence,
            'reservation_enabled' => true,
        ]);

        return $product;
    }

    /**
     * @return ProductCategory
     */
    protected function makeProductCategory(): ProductCategory
    {
        $name = $this->faker->name;

        $category = ProductCategory::create([
            'key' => Str::slug($name),
        ]);

        $category->translateOrNew(app()->getLocale())->fill([
            'name' => $name,
        ])->save();

        return $category;
    }

    /**
     * @param Browser $browser
     * @param Organization $provider
     * @param int $count
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return MainSearchFilterTest
     */
    protected function assertFilterByProvider(Browser $browser, Organization $provider, int $count = 1): static
    {
        $browser->waitFor('@selectControlProviders');
        $browser->click('@selectControlProviders .select-control-search');
        $this->findOptionElement($browser, '@selectControlProviders', $provider->name)->click();

        $this->assertProviderVisible($browser, $provider, $count);

        return $this;
    }

    /**
     * @param Browser $browser
     * @param Product $product
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return MainSearchFilterTest
     */
    protected function assertFilterByCategory(Browser $browser, Product $product): static
    {
        $browser->waitFor('@selectControlCategories');
        $browser->click('@selectControlCategories .select-control-search');
        $this->findOptionElement($browser, '@selectControlCategories', $product->product_category->name)->click();

        $this->assertProductVisible($browser, $product);

        return $this;
    }

    /**
     * @param Browser $browser
     * @param Fund $fund
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeOutException
     * @return MainSearchFilterTest
     */
    protected function assertFilterByFund(Browser $browser, Fund $fund): static
    {
        $browser->waitFor('@selectControlFunds');
        $browser->click('@selectControlFunds .select-control-search');
        $this->findOptionElement($browser, '@selectControlFunds', $fund->name)->click();

        $this->assertFundVisible($browser, $fund);

        return $this;
    }

    /**
     * @param Browser $browser
     * @param Fund $fund
     * @throws TimeoutException
     * @return MainSearchFilterTest
     */
    protected function assertMainSearchFundIsWorking(Browser $browser, Fund $fund): static
    {
        $browser->waitFor('@searchListSearch');
        $browser->typeSlowly('@searchListSearch', $fund->name, 50);

        $browser->waitFor("@listFundsRow$fund->id");
        $browser->assertVisible("@listFundsRow$fund->id");

        $this->assertWebshopRowsCount($browser, 1, '@searchListContent');

        $this->clearField($browser, '@searchListSearch');
        $this->fillSearchForEmptyResults($browser);

        return $this;
    }

    /**
     * @param Browser $browser
     * @param Organization $provider
     * @throws TimeoutException
     * @return MainSearchFilterTest
     */
    protected function assertMainSearchProviderIsWorking(Browser $browser, Organization $provider): static
    {
        $browser->waitFor('@searchListSearch');
        $browser->typeSlowly('@searchListSearch', $provider->name, 50);

        $browser->waitFor("@listProvidersRow$provider->id");
        $browser->assertVisible("@listProvidersRow$provider->id");

        // assert visible 2 rows - provider and provider product
        $this->assertWebshopRowsCount($browser, 2, '@searchListContent');

        $this->clearField($browser, '@searchListSearch');
        $this->fillSearchForEmptyResults($browser);

        return $this;
    }

    /**
     * @param Browser $browser
     * @param Product $product
     * @throws TimeoutException
     * @return MainSearchFilterTest
     */
    protected function assertMainSearchProductIsWorking(Browser $browser, Product $product): static
    {
        $browser->waitFor('@searchListSearch');
        $browser->typeSlowly('@searchListSearch', $product->name, 50);

        $browser->waitFor("@listProductsRow$product->id");
        $browser->assertVisible("@listProductsRow$product->id");

        $this->assertWebshopRowsCount($browser, 1, '@searchListContent');

        $this->clearField($browser, '@searchListSearch');
        $this->fillSearchForEmptyResults($browser);

        return $this;
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return MainSearchFilterTest
     */
    protected function fillSearchForEmptyResults(Browser $browser): static
    {
        $this->searchWebshopList($browser, '@searchList', '###############', null, 0);
        $this->clearField($browser, '@searchListSearch');

        return $this;
    }

    /**
     * @param Browser $browser
     * @param Product $product
     * @param int $count
     * @throws TimeoutException
     * @return MainSearchFilterTest
     */
    protected function assertProductVisible(Browser $browser, Product $product, int $count = 1): static
    {
        $browser->waitFor("@listProductsRow$product->id");
        $browser->assertVisible("@listProductsRow$product->id");
        $this->assertWebshopRowsCount($browser, $count, '@searchListContent');

        return $this;
    }

    /**
     * @param Browser $browser
     * @param Organization $provider
     * @param int $count
     * @throws TimeoutException
     * @return MainSearchFilterTest
     */
    protected function assertProviderVisible(Browser $browser, Organization $provider, int $count = 1): static
    {
        $browser->waitFor("@listProvidersRow$provider->id");
        $browser->assertVisible("@listProvidersRow$provider->id");
        $this->assertWebshopRowsCount($browser, $count, '@searchListContent');

        return $this;
    }

    /**
     * @param Browser $browser
     * @param Fund $fund
     * @throws TimeoutException
     * @return MainSearchFilterTest
     */
    protected function assertFundVisible(Browser $browser, Fund $fund): static
    {
        $browser->waitFor("@listFundsRow$fund->id");
        $browser->assertVisible("@listFundsRow$fund->id");
        $this->assertWebshopRowsCount($browser, 1, '@searchListContent');

        return $this;
    }

    /**
     * @param Browser $browser
     * @param string $option
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return MainSearchFilterTest
     */
    protected function toggleFilterTypeOptions(Browser $browser, string $option): static
    {
        $browser->waitFor("@searchType_$option");
        $browser->click("@searchType_$option");

        return $this;
    }

    /**
     * @param Browser $browser
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return MainSearchFilterTest
     */
    protected function clearFundSelect(Browser $browser): static
    {
        $browser->waitFor('@selectControlFunds');
        $browser->click('@selectControlFunds .select-control-search');
        $this->findOptionElement($browser, '@selectControlFunds', 'Selecteer tegoeden...')->click();

        return $this;
    }

    /**
     * @param Browser $browser
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return MainSearchFilterTest
     */
    protected function clearProviderSelect(Browser $browser): static
    {
        $browser->waitFor('@selectControlProviders');
        $browser->click('@selectControlProviders .select-control-search');
        $this->findOptionElement($browser, '@selectControlProviders', 'Selecteer aanbieder...')->click();

        return $this;
    }

    /**
     * @param Browser $browser
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return MainSearchFilterTest
     */
    protected function clearCategorySelect(Browser $browser): static
    {
        $browser->waitFor('@selectControlCategories');
        $browser->click('@selectControlCategories .select-control-search');
        $this->findOptionElement($browser, '@selectControlCategories', 'Selecteer categorie...')->click();

        return $this;
    }
}
