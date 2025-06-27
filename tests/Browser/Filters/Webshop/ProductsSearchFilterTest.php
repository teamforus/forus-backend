<?php

namespace Tests\Browser\Filters\Webshop;

use App\Models\BusinessType;
use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\VoucherTransaction;
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
use Tests\Traits\MakesTestOrganizationOffices;
use Throwable;

class ProductsSearchFilterTest extends DuskTestCase
{
    use MakesTestFunds;
    use HasFrontendActions;
    use RollbackModelsTrait;
    use MakesTestOrganizationOffices;

    /**
     * @throws Throwable
     * @return void
     */
    public function testProductsFilters(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $implementation = Implementation::general();

        $fund = $this->makeTestFund($organization, fundConfigsData: [
            'implementation_id' => $implementation->id,
        ]);

        $product = $this->prepareProduct($fund);
        $providerOrganization = $this->prepareProvider($product->organization);

        $this->rollbackModels([], function () use ($implementation, $fund, $providerOrganization, $product) {
            $this->browse(function (Browser $browser) use ($implementation, $fund, $providerOrganization, $product) {
                $browser->visit($implementation->urlWebshop('aanbod'));

                $this->assertProductsSearchIsWorking($browser, $providerOrganization, $product)
                    ->fillSearchForEmptyResults($browser);

                $this->assertProductsSearchByOrganization($browser, $providerOrganization, $product)
                    ->fillSearchForEmptyResults($browser);

                $this->assertProductsSearchBySubCategory($browser, $product)
                    ->fillSearchForEmptyResults($browser);

                $this->assertProductsSearchByFund($browser, $product, $fund)
                    ->fillSearchForEmptyResults($browser);

                $this->assertProductsSearchByDistance($browser, $providerOrganization, $product)
                    ->fillSearchForEmptyResults($browser);

                $this->assertProductsSearchByPrice($browser, $product)
                    ->fillSearchForEmptyResults($browser);

                $this->assertProductsSearchByOptions($browser, $product);
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
    public function testProvidersSorting(): void
    {
        $orderByColumns = [
            'created_at' => ['asc', 'desc'],
            'price' => ['asc', 'desc'],
            'name' => ['asc', 'desc'],
            'most_popular' => ['desc'],
        ];

        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $implementation = Implementation::general();

        $fund = $this->makeTestFund($organization, fundConfigsData: [
            'implementation_id' => $implementation->id,
        ]);

        [$product, $product2] = $this->makeProducts($fund);

        $this->rollbackModels([], function () use ($fund, $product, $product2, $orderByColumns) {
            $this->browse(function (Browser $browser) use ($fund, $product, $product2, $orderByColumns) {
                $implementation = $fund->refresh()->getImplementation();
                $browser->visit($implementation->urlWebshop('aanbod'))->refresh();
                $browser->waitFor($this->getWebshopRowsSelector());

                $this->assertProductsSearchByFund($browser, $product, $fund, 2);

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
        // create first product
        $product = $this->prepareProduct($fund);
        $product->name = 'animal';
        $product->price = 5;
        $product->created_at = $product->created_at->clone()->subDay();
        $product->save();

        // create second product and transaction (for the most popular sorting)
        $product2 = $this->prepareProduct($fund);
        $product2->update([
            'name' => 'xerox',
            'price' => 10,
        ]);

        $fund
            ->makeVoucher($this->makeIdentity())
            ->makeTransaction([
                'amount' => $product2->price,
                'product_id' => $product2->id,
                'target' => VoucherTransaction::TARGET_PROVIDER,
                'organization_id' => $product2->organization_id,
            ])
            ->setPaid(null, now());

        return [$product, $product2];
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
     * @return ProductsSearchFilterTest
     */
    protected function assertProductsSorting(
        Browser $browser,
        Product $product,
        Product $product2,
        string $column,
        string $dir
    ): static {
        $name = match ($column) {
            'name' => $dir === 'asc' ? 'Naam (A-Z)' : 'Naam (Z-A)',
            'price' => $dir === 'asc' ? 'Prijs (laag-hoog)' : 'Prijs (hoog-laag)',
            'created_at' => $dir === 'asc' ? 'Oudste eerst' : 'Nieuwe eerst',
            'most_popular' => 'Meest gewild',
            default => throw new InvalidArgumentException("Unsupported order by column: $column"),
        };

        $browser->waitFor('@selectControlOrderBy');
        $browser->click('@selectControlOrderBy .select-control-search');
        $this->findOptionElement($browser, '@selectControlOrderBy', $name)->click();

        $this->assertProductVisible($browser, $product, 2);
        $this->assertProductVisible($browser, $product2, 2);

        if ($dir === 'asc') {
            $browser->waitForTextIn($this->getWebshopRowsSelector() . ':nth-child(1)', $product->name);
            $browser->waitForTextIn($this->getWebshopRowsSelector() . ':nth-child(2)', $product2->name);
        } else {
            $browser->waitForTextIn($this->getWebshopRowsSelector() . ':nth-child(1)', $product2->name);
            $browser->waitForTextIn($this->getWebshopRowsSelector() . ':nth-child(2)', $product->name);
        }

        return $this;
    }

    /**
     * @param Organization $providerOrganization
     * @return Organization
     */
    protected function prepareProvider(Organization $providerOrganization): Organization
    {
        $typeName = $this->faker->name;

        $type = BusinessType::create([
            'key' => Str::slug($typeName),
        ]);

        $type->translateOrNew(app()->getLocale())->fill([
            'name' => $typeName,
        ])->save();

        $providerOrganization->update([
            'business_type_id' => $type->id,
            'email_public' => true,
            'email' => $this->faker->email,
            'phone_public' => true,
            'phone' => $this->faker->phoneNumber,
            'website_public' => true,
            'website' => $this->faker->url,
            'description_text' => $this->faker->sentence,
        ]);

        $employee = $providerOrganization->addEmployee($this->makeIdentity(), Role::pluck('id')->toArray());

        $office = $this->makeOrganizationOffice($providerOrganization, [
            'branch_id' => $this->faker->numberBetween(100000, 1000000),
            'branch_name' => $this->faker->name,
            'branch_number' => $this->faker->numberBetween(100000, 1000000),
            'lon' => config('forus.office.default_lng'),
            'lat' => config('forus.office.default_lat'),
            'postcode' => $this->faker->postcode,
        ]);

        $employee->update(['office_id' => $office->id]);

        return $providerOrganization;
    }

    /**
     * @param Fund $fund
     * @return Product
     */
    protected function prepareProduct(Fund $fund): Product
    {
        $product = $this->makeProductsFundFund(1)[0];
        $this->addProductFundToFund($fund, $product, false);

        $base = $this->makeProductCategory();
        $category = $this->makeProductCategory($base->id);

        $product->update([
            'product_category_id' => $category->id,
            'description_text' => $this->faker->sentence,
            'reservation_enabled' => true,
        ]);

        return $product;
    }

    /**
     * @param int|null $parentId
     * @return ProductCategory
     */
    protected function makeProductCategory(?int $parentId = null): ProductCategory
    {
        $name = $this->faker->name;

        $category = ProductCategory::create([
            'key' => Str::slug($name),
            'parent_id' => $parentId,
        ]);

        $category->translateOrNew(app()->getLocale())->fill([
            'name' => $name,
        ])->save();

        return $category;
    }

    /**
     * @param Browser $browser
     * @param Organization $provider
     * @param Product $product
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeOutException
     * @return ProductsSearchFilterTest
     */
    protected function assertProductsSearchByOrganization(
        Browser $browser,
        Organization $provider,
        Product $product
    ): static {
        $browser->waitFor('@selectControlOrganizations');
        $browser->click('@selectControlOrganizations .select-control-search');
        $this->findOptionElement($browser, '@selectControlOrganizations', $provider->name)->click();

        $this->assertProductVisible($browser, $product);

        return $this;
    }

    /**
     * @param Browser $browser
     * @param Product $product
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return ProductsSearchFilterTest
     */
    protected function assertProductsSearchBySubCategory(Browser $browser, Product $product): static
    {
        $browser->waitFor('@selectControlCategories');
        $browser->click('@selectControlCategories .select-control-search');
        $this->findOptionElement($browser, '@selectControlCategories', $product->product_category->parent->name)->click();

        $browser->waitFor('@selectControlSubCategories');
        $browser->click('@selectControlSubCategories .select-control-search');
        $this->findOptionElement($browser, '@selectControlSubCategories', $product->product_category->name)->click();

        $this->assertProductVisible($browser, $product);

        return $this;
    }

    /**
     * @param Browser $browser
     * @param Product $product
     * @param Fund $fund
     * @param int $count
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return ProductsSearchFilterTest
     */
    protected function assertProductsSearchByFund(
        Browser $browser,
        Product $product,
        Fund $fund,
        int $count = 1,
    ): static {
        $browser->waitFor('@selectControlFunds');
        $browser->click('@selectControlFunds .select-control-search');
        $this->findOptionElement($browser, '@selectControlFunds', $fund->name)->click();

        $this->assertProductVisible($browser, $product, $count);

        return $this;
    }

    /**
     * @param Browser $browser
     * @param Organization $provider
     * @param Product $product
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeOutException
     * @return ProductsSearchFilterTest
     */
    protected function assertProductsSearchByDistance(
        Browser $browser,
        Organization $provider,
        Product $product
    ): static {
        $browser->waitFor('@inputPostcode');
        $browser->typeSlowly('@inputPostcode', $provider->offices[0]->postcode, 50);

        $browser->waitFor('@selectControlDistances');
        $browser->click('@selectControlDistances .select-control-search');
        $this->findOptionElement($browser, '@selectControlDistances', '< 10 km')->click();

        $this->assertProductVisible($browser, $product);

        return $this;
    }

    /**
     * @param Browser $browser
     * @param Product $product
     * @throws TimeOutException
     * @return ProductsSearchFilterTest
     */
    protected function assertProductsSearchByPrice(Browser $browser, Product $product): static
    {
        $browser->waitFor('@inputPriceFrom');
        $browser->typeSlowly('@inputPriceFrom', $product->price - 5, 50);

        $this->assertProductVisible($browser, $product);
        $this->fillSearchForEmptyResults($browser);

        $browser->typeSlowly('@inputPriceFrom', $product->price + 5, 50);

        $this->assertWebshopRowsCount($browser, 0, '@listProductsContent');

        $this->clearField($browser, '@inputPriceFrom');
        $browser->typeSlowly('@inputPriceTo', $product->price + 5, 50);

        $this->assertProductVisible($browser, $product);
        $this->fillSearchForEmptyResults($browser);

        $browser->typeSlowly('@inputPriceTo', $product->price - 5, 50);

        $this->assertWebshopRowsCount($browser, 0, '@listProductsContent');

        $browser->typeSlowly('@inputPriceFrom', $product->price - 5, 50);
        $browser->typeSlowly('@inputPriceTo', $product->price + 5, 50);

        $this->assertProductVisible($browser, $product);

        return $this;
    }

    /**
     * @param Browser $browser
     * @param Product $product
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return ProductsSearchFilterTest
     */
    protected function assertProductsSearchByOptions(Browser $browser, Product $product): static
    {
        $browser->waitFor('@paymentOptionQr');
        $browser->click('@paymentOptionQr');

        $this->assertProductVisible($browser, $product);
        $this->fillSearchForEmptyResults($browser);

        $browser->waitFor('@paymentOptionReservation');
        $browser->click('@paymentOptionReservation');

        $this->assertProductVisible($browser, $product);
        $this->fillSearchForEmptyResults($browser);

        $browser->waitFor('@paymentOptionIdeal');
        $browser->click('@paymentOptionIdeal');

        $this->assertWebshopRowsCount($browser, 0, '@listProductsContent');

        return $this;
    }

    /**
     * @param Browser $browser
     * @param Organization $provider
     * @param Product $product
     * @throws TimeOutException
     * @return ProductsSearchFilterTest
     */
    protected function assertProductsSearchIsWorking(Browser $browser, Organization $provider, Product $product): static
    {
        $this->searchWebshopList($browser, '@listProducts', $product->name, $product->id);
        $this->clearField($browser, '@listProductsSearch');

        $this->fillSearchForEmptyResults($browser);

        $this->searchWebshopList($browser, '@listProducts', $product->description_text, $product->id);
        $this->clearField($browser, '@listProductsSearch');

        $this->fillSearchForEmptyResults($browser);

        $this->searchWebshopList($browser, '@listProducts', $provider->name, $product->id);
        $this->clearField($browser, '@listProductsSearch');

        $this->fillSearchForEmptyResults($browser);

        $this->searchWebshopList($browser, '@listProducts', $provider->description_text, $product->id);
        $this->clearField($browser, '@listProductsSearch');

        $this->fillSearchForEmptyResults($browser);

        $this->searchWebshopList($browser, '@listProducts', $product->product_category->name, $product->id);
        $this->clearField($browser, '@listProductsSearch');

        return $this;
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return ProductsSearchFilterTest
     */
    protected function fillSearchForEmptyResults(Browser $browser): static
    {
        $this->searchWebshopList($browser, '@listProducts', '###############', null, 0);
        $this->clearField($browser, '@listProductsSearch');

        return $this;
    }

    /**
     * @param Browser $browser
     * @param Product $product
     * @param int $count
     * @throws TimeoutException
     * @return ProductsSearchFilterTest
     */
    protected function assertProductVisible(Browser $browser, Product $product, int $count = 1): static
    {
        $browser->waitFor("@listProductsRow$product->id");
        $browser->assertVisible("@listProductsRow$product->id");
        $this->assertWebshopRowsCount($browser, $count, '@listProductsContent');

        return $this;
    }
}
