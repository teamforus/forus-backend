<?php

namespace Tests\Browser\Filters\Webshop;

use App\Models\BusinessType;
use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use Facebook\WebDriver\Exception\ElementClickInterceptedException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeOutException;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Laravel\Dusk\Browser;
use Throwable;

class ProvidersWebshopSearchFilterTest extends BaseWebshopSearchFilter
{
    /**
     * @return string
     */
    public function getListSelector(): string
    {
        return '@listProviders';
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProvidersFilters(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $fund = $this->makeTestFund($organization, implementation: Implementation::byKey('nijmegen'));

        $products = $this->prepareProducts($fund);
        $provider = $this->prepareProvider($products[0]->organization);

        $this->rollbackModels([], function () use ($fund, $provider, $products) {
            $this->browse(function (Browser $browser) use ($fund, $provider, $products) {
                $browser->visit($fund->urlWebshop('aanbieders'));

                $this->fillListSearchForEmptyResults($browser);
                $this->assertProvidersSearchIsWorking($browser, $provider);

                $this->fillListSearchForEmptyResults($browser);
                $this->assertListFilterByBusinessType($browser, $provider->business_type, $provider->id, 1);

                $this->fillListSearchForEmptyResults($browser);
                $this->assertListFilterByProductCategory($browser, $provider->id, $products[0]->product_category);

                $this->fillListSearchForEmptyResults($browser);
                $this->assertListFilterByFund($browser, $fund, $provider->id, 1, true);

                $this->fillListSearchForEmptyResults($browser);
                $this->assertListFilterByDistance($browser, $provider->offices[0]->postcode, $provider->id);
            });
        }, function () use ($fund, $products) {
            $fund && $this->deleteFund($fund);
            array_walk($products, fn (Product $product) => $product->delete());
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProvidersSorting(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $fund = $this->makeTestFund($organization, implementation: Implementation::byKey('nijmegen'));

        $provider = $this->prepareProvider($this->prepareProducts($fund)[0]->organization);
        $provider2 = $this->prepareProvider($this->prepareProducts($fund)[0]->organization);

        $provider->update(['name' => 'Driving school']);
        $provider2->update(['name' => 'Library']);

        $this->rollbackModels([], function () use ($fund, $provider, $provider2) {
            $this->browse(function (Browser $browser) use ($fund, $provider, $provider2) {
                $browser->visit($fund->urlWebshop('aanbieders'))->refresh();

                $browser->waitFor('@productFilterGroupFunds');
                $this->uncollapseWebshopFilterGroup($browser, '@productFilterGroupFunds');

                $browser->waitFor('@productFilterFundItem' . $fund->id);
                $browser->click('@productFilterFundItem' . $fund->id);

                $this->assertListVisibility($browser, $provider->id, true, 2);
                $this->assertListVisibility($browser, $provider2->id, true, 2);

                $this->assertProvidersSorting($browser, $provider, $provider2, 'name', 'asc');
                $this->assertProvidersSorting($browser, $provider, $provider2, 'name', 'desc');
            });
        }, function () use ($fund, $provider, $provider2) {
            $fund && $this->deleteFund($fund);
            $provider->products->each(fn (Product $product) => $product->delete());
            $provider2->products->each(fn (Product $product) => $product->delete());
        });
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
            'website' => 'https://' . $this->faker->domainName,
        ]);

        $employee = $providerOrganization->addEmployee($this->makeIdentity(), Role::pluck('id')->toArray());

        $office = $this->makeOrganizationOffice($providerOrganization, [
            'branch_id' => $this->faker->numberBetween(100000, 1000000),
            'branch_name' => $this->faker->name,
            'branch_number' => $this->faker->numberBetween(100000, 1000000),
            'lon' => 6,
            'lat' => 49,
            'postcode' => $this->faker->postcode,
        ]);

        $employee->update(['office_id' => $office->id]);

        return $providerOrganization->refresh();
    }

    /**
     * @param Fund $fund
     * @return Product[]
     */
    protected function prepareProducts(Fund $fund): array
    {
        $products = $this->makeTestProviderWithProducts(2);
        array_walk($products, fn (Product $product) => $this->addProductToFund($fund, $product, false));

        $this->addCategoryToProduct($products[0]);
        $this->addCategoryToProduct($products[1], $products[0]->product_category_id);

        return $products;
    }

    /**
     * @param Product $product
     * @param int|null $parentId
     * @return void
     */
    protected function addCategoryToProduct(Product $product, ?int $parentId = null): void
    {
        $name = $this->faker->name;

        $category = ProductCategory::create([
            'key' => Str::slug($name),
            'parent_id' => $parentId,
        ]);

        $category->translateOrNew(app()->getLocale())->fill([
            'name' => $name,
        ])->save();

        $product->update([
            'product_category_id' => $category->id,
        ]);
    }

    /**
     * @param Browser $browser
     * @param Organization $provider
     * @param Organization $provider2
     * @param string $column
     * @param string $dir
     * @throws NoSuchElementException
     * @throws TimeOutException
     * @throws ElementClickInterceptedException
     * @return void
     */
    protected function assertProvidersSorting(
        Browser $browser,
        Organization $provider,
        Organization $provider2,
        string $column,
        string $dir,
    ): void {
        $name = match ($column) {
            'name' => $dir === 'asc' ? 'Naam (oplopend)' : 'Naam (aflopend)',
            default => throw new InvalidArgumentException("Unsupported order by column: $column"),
        };

        $this->changeSelectControl($browser, '@selectControlOrderBy', $name);
        $this->assertListVisibility($browser, $provider->id, true, 2);
        $this->assertListVisibility($browser, $provider2->id, true, 2);

        if ($dir === 'asc') {
            $browser->waitForTextIn($this->getWebshopRowsSelector() . ':nth-child(1)', $provider->name);
            $browser->waitForTextIn($this->getWebshopRowsSelector() . ':nth-child(2)', $provider2->name);
        } else {
            $browser->waitForTextIn($this->getWebshopRowsSelector() . ':nth-child(1)', $provider2->name);
            $browser->waitForTextIn($this->getWebshopRowsSelector() . ':nth-child(2)', $provider->name);
        }
    }

    /**
     * @param Browser $browser
     * @param Organization $provider
     * @throws TimeOutException
     * @return void
     */
    protected function assertProvidersSearchIsWorking(Browser $browser, Organization $provider): void
    {
        $this->assertListFilterQueryValue($browser, $provider->name, $provider->id);
        $this->assertListFilterQueryValue($browser, $provider->email, $provider->id);
        $this->assertListFilterQueryValue($browser, $provider->phone, $provider->id);
        $this->assertListFilterQueryValue($browser, $provider->website, $provider->id);
        $this->assertListFilterQueryValue($browser, $provider->offices[0]->address, $provider->id);
        $this->assertListFilterQueryValue($browser, $provider->business_type->name, $provider->id);
    }
}
