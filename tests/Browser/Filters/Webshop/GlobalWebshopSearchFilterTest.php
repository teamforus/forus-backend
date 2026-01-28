<?php

namespace Tests\Browser\Filters\Webshop;

use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Product;
use Facebook\WebDriver\Exception\ElementClickInterceptedException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeOutException;
use InvalidArgumentException;
use Laravel\Dusk\Browser;
use Throwable;

class GlobalWebshopSearchFilterTest extends BaseWebshopSearchFilter
{
    public function getListSelector(): string
    {
        return '@searchList';
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testMainFilters(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization, implementation: Implementation::byKey('nijmegen'));
        $product = $this->prepareProduct($fund);

        $this->rollbackModels([], function () use ($fund, $product) {
            $this->browse(function (Browser $browser) use ($fund, $product) {
                $providerOrganization = $product->organization;

                $browser->visit($fund->urlWebshop('search'));
                $browser->waitFor($this->getWebshopRowsSelector());

                $this->fillListSearchForEmptyResults($browser);
                $this->assertMainSearchFundIsWorking($browser, $fund);

                $this->fillListSearchForEmptyResults($browser);
                $this->assertMainSearchProviderIsWorking($browser, $providerOrganization);

                $this->fillListSearchForEmptyResults($browser);
                $this->assertMainSearchProductIsWorking($browser, $product);

                $this->toggleFilterTypeOptions($browser, 'providers');
                $this->fillListSearchForEmptyResults($browser);
                $this->assertFilterByProvider($browser, $providerOrganization);
                $this->toggleFilterTypeOptions($browser, 'providers');

                $this->toggleFilterTypeOptions($browser, 'products');
                $this->fillListSearchForEmptyResults($browser);
                $this->assertFilterByCategory($browser, $product);
                $this->toggleFilterTypeOptions($browser, 'products');

                $this->toggleFilterTypeOptions($browser, 'funds');
                $this->fillListSearchForEmptyResults($browser);
                $this->assertFilterByFund($browser, $fund);
                $this->toggleFilterTypeOptions($browser, 'funds');
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
        $fund = $this->makeTestFund($organization, implementation: Implementation::byKey('nijmegen'));

        [$product, $product2] = $this->makeProducts($fund);

        $this->rollbackModels([], function () use ($fund, $product, $product2, $orderByColumns) {
            $this->browse(function (Browser $browser) use ($fund, $product, $product2, $orderByColumns) {
                $browser->visit($fund->urlWebshop('search'))->refresh();
                $browser->waitFor($this->getWebshopRowsSelector());

                $this->toggleFilterTypeOptions($browser, 'products');
                $this->changeSelectControl($browser, '@selectControlOrganizations', text: $product->organization->name);
                $this->fillListSearchForEmptyResults($browser);

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
        $products = $this->makeTestProviderWithProducts(2);
        array_walk($products, fn (Product $product) => $this->addProductToFund($fund, $product, false));

        $products[0]->forceFill([
            'created_at' => $products[0]->created_at->clone()->subDay(),
        ])->save();

        return $products;
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
     * @return GlobalWebshopSearchFilterTest
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

        $this->changeSelectControl($browser, '@selectControlOrderBy', $name);

        $this->assertProductVisible($browser, $product, 2);
        $this->assertProductVisible($browser, $product2, 2);

        $browser->waitUsing(null, 100, function () use ($browser, $dir, $product, $product2) {
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
        $product = $this->makeTestProviderWithProducts(1)[0];
        $this->addProductToFund($fund, $product, false);

        $product->forceFill([
            'product_category_id' => $this->makeProductCategory()->id,
            'description_text' => $this->faker->sentence(),
            'reservation_enabled' => true,
        ])->save();

        return $product;
    }

    /**
     * @param Browser $browser
     * @param Organization $provider
     * @param int $count
     * @throws TimeoutException
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @return void
     */
    protected function assertFilterByProvider(Browser $browser, Organization $provider, int $count = 1): void
    {
        $this->changeSelectControl($browser, '@selectControlOrganizations', $provider->name);
        $this->assertListVisibility($browser, $provider->id, true, totalRows: $count, listSelector: '@listProviders');
        $this->changeSelectControl($browser, '@selectControlOrganizations', index: 0);
    }

    /**
     * @param Browser $browser
     * @param Product $product
     * @throws TimeoutException
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @return void
     */
    protected function assertFilterByCategory(Browser $browser, Product $product): void
    {
        $this->changeSelectControl($browser, '@selectControlCategories', $product->product_category->name);
        $this->assertListVisibility($browser, $product->id, true, totalRows: 1, listSelector: '@listProducts');
        $this->changeSelectControl($browser, '@selectControlCategories', index: 0);
    }

    /**
     * @param Browser $browser
     * @param Fund $fund
     * @throws TimeOutException
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @return void
     */
    protected function assertFilterByFund(Browser $browser, Fund $fund): void
    {
        $this->changeSelectControl($browser, '@selectControlFunds', $fund->name);
        $this->assertListVisibility($browser, $fund->id, true, totalRows: 1, listSelector: '@listFunds');
        $this->changeSelectControl($browser, '@selectControlFunds', index: 1);
    }

    /**
     * @param Browser $browser
     * @param Fund $fund
     * @throws TimeoutException
     * @return void
     */
    protected function assertMainSearchFundIsWorking(Browser $browser, Fund $fund): void
    {
        $browser->waitFor('@searchListSearch');
        $browser->typeSlowly('@searchListSearch', $fund->name, 0);

        $browser->waitFor("@listFundsRow$fund->id");
        $browser->assertVisible("@listFundsRow$fund->id");

        $this->assertWebshopRowsCount($browser, 1, '@searchListContent');

        $this->clearField($browser, '@searchListSearch');
        $this->fillListSearchForEmptyResults($browser);
    }

    /**
     * @param Browser $browser
     * @param Organization $provider
     * @throws TimeoutException
     * @return void
     */
    protected function assertMainSearchProviderIsWorking(Browser $browser, Organization $provider): void
    {
        $browser->waitFor('@searchListSearch');
        $browser->typeSlowly('@searchListSearch', $provider->name, 0);

        $browser->waitFor("@listProvidersRow$provider->id");
        $browser->assertVisible("@listProvidersRow$provider->id");

        // assert visible 2 rows - provider and provider product
        $this->assertWebshopRowsCount($browser, 2, '@searchListContent');

        $this->clearField($browser, '@searchListSearch');
        $this->fillListSearchForEmptyResults($browser);
    }

    /**
     * @param Browser $browser
     * @param Product $product
     * @throws TimeoutException
     * @return void
     */
    protected function assertMainSearchProductIsWorking(Browser $browser, Product $product): void
    {
        $browser->waitFor('@searchListSearch');
        $browser->typeSlowly('@searchListSearch', $product->name, 0);

        $browser->waitFor("@listProductsRow$product->id");
        $browser->assertVisible("@listProductsRow$product->id");

        $this->assertWebshopRowsCount($browser, 1, '@searchListContent');

        $this->clearField($browser, '@searchListSearch');
        $this->fillListSearchForEmptyResults($browser);
    }

    /**
     * @param Browser $browser
     * @param string $option
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    protected function toggleFilterTypeOptions(Browser $browser, string $option): void
    {
        $browser->waitFor("@searchType_$option");
        $browser->click("@searchType_$option");
    }

    /**
     * @param Browser $browser
     * @param Product $product
     * @param int $count
     * @throws TimeoutException
     * @return void
     */
    protected function assertProductVisible(Browser $browser, Product $product, int $count = 1): void
    {
        $browser->waitFor("@listProductsRow$product->id");
        $browser->assertVisible("@listProductsRow$product->id");
        $this->assertWebshopRowsCount($browser, $count, '@searchListContent');
    }
}
