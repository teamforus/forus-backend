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
use Throwable;

class ProductsWebshopSearchFilterTest extends BaseWebshopSearchFilter
{
    /**
     * @return string
     */
    public function getListSelector(): string
    {
        return '@listProducts';
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProductsFilters(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $fund = $this->makeTestFund($organization, implementation: Implementation::byKey('nijmegen'));

        $products = $this->setupProducts($fund, 3, 10);
        $products[1]->update(['price' => 5]);
        $products[2]->update(['price' => 20]);

        $provider = $this->prepareProvider($products[0]->organization);

        $this->rollbackModels([], function () use ($fund, $provider, $products) {
            $this->browse(function (Browser $browser) use ($fund, $provider, $products) {
                $browser->visit($fund->urlWebshop('aanbod'));

                $this->fillListSearchForEmptyResults($browser);
                $this->assertProductsSearchIsWorking($browser, $products[0]);

                $this->fillListSearchForEmptyResults($browser);
                $this->assertListFilterByOrganization($browser, $provider, $products[0]->id, count($products));

                $this->fillListSearchForEmptyResults($browser);
                $this->assertListFilterByProductCategory($browser, $products[0]->id, $products[0]->product_category);

                $this->fillListSearchForEmptyResults($browser);
                $this->assertListFilterByFund($browser, $fund, $products[0]->id, count($products), true);

                $this->fillListSearchForEmptyResults($browser);
                $this->assertListFilterByDistance($browser, $provider->offices[0]->postcode, $products[0]->id);

                $this->fillListSearchForEmptyResults($browser);
                $this->assertProductsFilterByPrice($browser, $products[0]);

                $this->fillListSearchForEmptyResults($browser);
                $this->assertProductsFilterByOptions($browser, $products[0]);
            });
        }, function () use ($fund, $products) {
            $this->deleteFund($fund);

            foreach ($products as $product) {
                $product->delete();
            }
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProductSorting(): void
    {
        $orderByColumns = [
            'created_at' => ['asc', 'desc'],
            'price' => ['asc', 'desc'],
            'name' => ['asc', 'desc'],
            'most_popular' => ['desc'],
        ];

        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization, implementation: Implementation::byKey('nijmegen'));

        [$product, $product2] = $this->makeProducts($fund);

        $this->rollbackModels([], function () use ($fund, $product, $product2, $orderByColumns) {
            $this->browse(function (Browser $browser) use ($fund, $product, $product2, $orderByColumns) {
                $browser->visit($fund->urlWebshop('aanbod'))->refresh();
                $browser->waitFor($this->getWebshopRowsSelector());

                $browser->waitFor('@productFilterGroupFunds');
                $this->uncollapseWebshopFilterGroup($browser, '@productFilterGroupFunds');

                $browser->waitFor('@productFilterFundItem' . $fund->id);
                $browser->click('@productFilterFundItem' . $fund->id);

                $this->assertListVisibility($browser, $product->id, true, 2);
                $this->assertListVisibility($browser, $product2->id, true, 2);

                foreach ($orderByColumns as $item => $dirs) {
                    array_walk($dirs, fn ($dir) => $this->assertListSorting($browser, $product, $product2, $item, $dir));
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
        $products = $this->setupProducts($fund, 2, 10);

        $products[0]->forceFill([
            'name' => 'Art supplies',
            'price' => 5,
            'created_at' => $products[1]->created_at->clone()->subDay(),
        ])->save();

        // create second product and transaction (for the most popular sorting)
        $products[1]->forceFill([
            'name' => 'Books',
            'price' => 10,
        ])->save();

        $fund
            ->makeVoucher($this->makeIdentity())
            ->makeTransaction([
                'target' => VoucherTransaction::TARGET_PROVIDER,
                'amount' => $products[1]->price,
                'product_id' => $products[1]->id,
                'organization_id' => $products[1]->organization_id,
            ])
            ->setPaid(null, now());

        return $products;
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
            'postcode' => '9721 AN',
            'lat' => 53.1935717,
            'lng' => 6.5825892,
        ]);

        $employee->update(['office_id' => $office->id]);

        return $providerOrganization;
    }

    /**
     * @param Fund $fund
     * @param int $count
     * @param float $price
     * @return Product[]
     */
    protected function setupProducts(Fund $fund, int $count, float $price): array
    {
        $products = $this->makeTestProviderWithProducts($count, $price);

        foreach ($products as $product) {
            $this->addProductToFund($fund, $product, false);
        }

        $base = $this->makeProductCategory();
        $category = $this->makeProductCategory($base->id);

        $products[0]->update([
            'description_text' => $this->faker->sentence,
            'product_category_id' => $category->id,
            'reservation_enabled' => true,
        ]);

        return $products;
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
     * @param Product $product
     * @throws NoSuchElementException
     * @throws TimeOutException
     * @throws ElementClickInterceptedException
     * @return void
     */
    protected function assertProductsFilterByPrice(Browser $browser, Product $product): void
    {
        $this->uncollapseWebshopFilterGroup($browser, '@productFilterGroupPrice');
        $this->changeSelectControl($browser, '@selectControlOrganizations', text: $product->organization->name);

        $this->assertListVisibility($browser, $product->id, true);
        $this->assertWebshopRowsCount($browser, 3, '@listProductsContent');

        $browser->waitFor('@inputPriceFrom');

        // search by min price (product price + 1) and assert product not visible
        $this->clearField($browser, '@listProductsSearch');
        $this->fillListSearchForEmptyResults($browser);

        $browser->clear('@inputPriceFrom');
        $browser->typeSlowly('@inputPriceFrom', $product->price + 1, 0);

        $this->assertListVisibility($browser, $product->id, false);
        $this->assertWebshopRowsCount($browser, 1, '@listProductsContent');

        // search by min price (product price - 1) and assert product is visible
        $this->clearField($browser, '@listProductsSearch');
        $this->fillListSearchForEmptyResults($browser);

        $browser->clear('@inputPriceFrom');
        $browser->typeSlowly('@inputPriceFrom', $product->price - 1, 0);

        $this->assertListVisibility($browser, $product->id, true);
        $this->assertWebshopRowsCount($browser, 2, '@listProductsContent');

        // search by max price (product price - 1) and assert product not visible
        $this->clearField($browser, '@listProductsSearch');
        $this->fillListSearchForEmptyResults($browser);

        $browser->clear('@inputPriceTo');
        $browser->typeSlowly('@inputPriceTo', $product->price - 1, 0);

        $this->assertListVisibility($browser, $product->id, false);
        $this->assertWebshopRowsCount($browser, 0, '@listProductsContent');

        // search by max price (product price + 1) and assert product is visible
        $this->clearField($browser, '@listProductsSearch');
        $this->fillListSearchForEmptyResults($browser);

        $browser->clear('@inputPriceTo');
        $browser->typeSlowly('@inputPriceTo', $product->price + 1, 0);

        $this->assertListVisibility($browser, $product->id, true);
        $this->assertWebshopRowsCount($browser, 1, '@listProductsContent');

        // clear results and filters
        $this->clearField($browser, '@listProductsSearch');
        $this->fillListSearchForEmptyResults($browser);

        $this->clearField($browser, '@inputPriceFrom');
        $browser->pause(1000);
        $browser->type('@inputPriceTo', 20);
        $browser->pause(1000);

        $this->assertListVisibility($browser, $product->id, true);
        $this->assertWebshopRowsCount($browser, 3, '@listProductsContent');

        $this->changeSelectControl($browser, '@selectControlOrganizations', index: 0);
    }

    /**
     * @param Browser $browser
     * @param Product $product
     * @throws TimeOutException
     * @return void
     */
    protected function assertProductsSearchIsWorking(Browser $browser, Product $product): void
    {
        $this->assertListFilterQueryValue($browser, $product->name, $product->id);
        $this->assertListFilterQueryValue($browser, $product->description_text, $product->id);
        $this->assertListFilterQueryValue($browser, $product->organization->name, $product->id, 3);
        $this->assertListFilterQueryValue($browser, $product->product_category->name, $product->id);
    }

    /**
     * @param Browser $browser
     * @param Product $product
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    protected function assertProductsFilterByOptions(Browser $browser, Product $product): void
    {
        $this->uncollapseWebshopFilterGroup($browser, '@productFiltersGroupReservationOptions');
        $this->changeSelectControl($browser, '@selectControlOrganizations', text: $product->organization->name);

        $browser->waitFor('@paymentOptionQr');
        $browser->click('@paymentOptionQr');

        $this->assertListVisibility($browser, $product->id, true);
        $this->fillListSearchForEmptyResults($browser);

        $browser->waitFor('@paymentOptionReservation');
        $browser->click('@paymentOptionReservation');

        $this->assertListVisibility($browser, $product->id, true);
        $this->fillListSearchForEmptyResults($browser);

        $browser->waitFor('@paymentOptionIdeal');
        $browser->click('@paymentOptionIdeal');

        $this->assertWebshopRowsCount($browser, 0, '@listProductsContent');

        $this->changeSelectControl($browser, '@selectControlOrganizations', index: 0);
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
     * @return void
     */
    protected function assertListSorting(
        Browser $browser,
        Product $product,
        Product $product2,
        string $column,
        string $dir,
    ): void {
        $name = match ($column) {
            'name' => $dir === 'asc' ? 'Naam (A-Z)' : 'Naam (Z-A)',
            'price' => $dir === 'asc' ? 'Prijs (laag-hoog)' : 'Prijs (hoog-laag)',
            'created_at' => $dir === 'asc' ? 'Oudste eerst' : 'Nieuwe eerst',
            'most_popular' => 'Meest gewild',
            default => throw new InvalidArgumentException("Unsupported order by column: $column"),
        };

        $this->changeSelectControl($browser, '@selectControlOrderBy', text: $name);

        $this->assertListVisibility($browser, $product->id, true);
        $this->assertListVisibility($browser, $product2->id, true);
        $this->assertWebshopRowsCount($browser, 2, $this->getListSelector() . 'Content');

        if ($dir === 'asc') {
            $browser->waitForTextIn($this->getWebshopRowsSelector() . ':nth-child(1)', $product->name);
            $browser->waitForTextIn($this->getWebshopRowsSelector() . ':nth-child(2)', $product2->name);
        } else {
            $browser->waitForTextIn($this->getWebshopRowsSelector() . ':nth-child(1)', $product2->name);
            $browser->waitForTextIn($this->getWebshopRowsSelector() . ':nth-child(2)', $product->name);
        }
    }
}
