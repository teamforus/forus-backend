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
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizationOffices;
use Throwable;

class ProvidersSearchFilterTest extends DuskTestCase
{
    use MakesTestFunds;
    use HasFrontendActions;
    use RollbackModelsTrait;
    use MakesTestOrganizationOffices;

    /**
     * @throws Throwable
     * @return void
     */
    public function testProvidersFilters(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $implementation = Implementation::general();

        $fund = $this->makeTestFund($organization, fundConfigsData: [
            'implementation_id' => $implementation->id,
        ]);

        $products = $this->prepareProducts($fund);
        $provider = $this->prepareProvider($products[0]->organization);

        $this->rollbackModels([], function () use ($implementation, $fund, $provider, $products) {
            $this->browse(function (Browser $browser) use ($implementation, $fund, $provider, $products) {
                $browser->visit($implementation->urlWebshop('aanbieders'));

                $this->assertProvidersSearchIsWorking($browser, $provider)
                    ->fillSearchForEmptyResults($browser);

                $this->assertProvidersSearchByBusinessType($browser, $provider)
                    ->fillSearchForEmptyResults($browser);

                $this->assertProvidersSearchByCategory($browser, $provider, $products[0])
                    ->fillSearchForEmptyResults($browser);

                $this->assertProvidersSearchBySubCategory($browser, $provider, $products[1])
                    ->fillSearchForEmptyResults($browser);

                $this->assertProvidersSearchByFund($browser, $provider, $fund)
                    ->fillSearchForEmptyResults($browser);

                $this->assertProvidersSearchByDistance($browser, $provider);
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
        $implementation = Implementation::general();

        $fund = $this->makeTestFund($organization, fundConfigsData: [
            'implementation_id' => $implementation->id,
        ]);

        $provider = $this->prepareProvider($this->prepareProducts($fund)[0]->organization);
        $provider2 = $this->prepareProvider($this->prepareProducts($fund)[0]->organization);

        $provider->update(['name' => 'animal']);
        $provider2->update(['name' => 'xerox']);

        $this->rollbackModels([], function () use ($fund, $provider, $provider2) {
            $this->browse(function (Browser $browser) use ($fund, $provider, $provider2) {
                $implementation = $fund->refresh()->getImplementation();
                $browser->visit($implementation->urlWebshop('aanbieders'))->refresh();

                $this->assertProvidersSearchByFund($browser, $provider, $fund, 2);

                $this->assertProvidersSorting($browser, $provider, $provider2, 'name', 'asc')
                    ->assertProvidersSorting($browser, $provider, $provider2, 'name', 'desc');
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
            'website' => $this->faker->url,
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

        return $providerOrganization->refresh();
    }

    /**
     * @param Fund $fund
     * @return Product[]
     */
    protected function prepareProducts(Fund $fund): array
    {
        $products = $this->makeProductsFundFund(2);
        array_walk($products, fn (Product $product) => $this->addProductFundToFund($fund, $product, false));

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
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return ProvidersSearchFilterTest
     */
    protected function assertProvidersSearchByBusinessType(Browser $browser, Organization $provider): static
    {
        $browser->waitFor('@selectControlBusinessTypes');
        $browser->click('@selectControlBusinessTypes .select-control-search');
        $this->findOptionElement($browser, '@selectControlBusinessTypes', $provider->business_type->name)->click();

        return $this->assertProviderVisible($browser, $provider);
    }

    /**
     * @param Browser $browser
     * @param Organization $provider
     * @param Product $product
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeOutException
     * @return ProvidersSearchFilterTest
     */
    protected function assertProvidersSearchByCategory(Browser $browser, Organization $provider, Product $product): static
    {
        $browser->waitFor('@selectControlCategories');
        $browser->click('@selectControlCategories .select-control-search');
        $this->findOptionElement($browser, '@selectControlCategories', $product->product_category->name)->click();

        return $this->assertProviderVisible($browser, $provider);
    }

    /**
     * @param Browser $browser
     * @param Organization $provider
     * @param Product $product
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return ProvidersSearchFilterTest
     */
    protected function assertProvidersSearchBySubCategory(Browser $browser, Organization $provider, Product $product): static
    {
        $browser->waitFor('@selectControlSubCategories');
        $browser->click('@selectControlSubCategories .select-control-search');
        $this->findOptionElement($browser, '@selectControlSubCategories', $product->product_category->name)->click();

        return $this->assertProviderVisible($browser, $provider);
    }

    /**
     * @param Browser $browser
     * @param Organization $provider
     * @param Fund $fund
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeOutException
     * @return ProvidersSearchFilterTest
     */
    protected function assertProvidersSearchByFund(
        Browser $browser,
        Organization $provider,
        Fund $fund,
        int $count = 1,
    ): static {
        $browser->waitFor('@selectControlFunds');
        $browser->click('@selectControlFunds .select-control-search');
        $this->findOptionElement($browser, '@selectControlFunds', $fund->name)->click();

        return $this->assertProviderVisible($browser, $provider, $count);
    }

    /**
     * @param Browser $browser
     * @param Organization $provider
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeOutException
     * @return ProvidersSearchFilterTest
     */
    protected function assertProvidersSearchByDistance(Browser $browser, Organization $provider): static
    {
        $browser->waitFor('@inputPostcode');
        $browser->typeSlowly('@inputPostcode', $provider->offices[0]->postcode, 50);

        $browser->waitFor('@selectControlDistances');
        $browser->click('@selectControlDistances .select-control-search');
        $this->findOptionElement($browser, '@selectControlDistances', '< 10 km')->click();

        return $this->assertProviderVisible($browser, $provider);
    }

    /**
     * @param Browser $browser
     * @param Organization $provider
     * @param Organization $provider2
     * @param string $column
     * @param string $dir
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeOutException
     * @return ProvidersSearchFilterTest
     */
    protected function assertProvidersSorting(
        Browser $browser,
        Organization $provider,
        Organization $provider2,
        string $column,
        string $dir
    ): static {
        $name = match ($column) {
            'name' => $dir === 'asc' ? 'Naam (oplopend)' : 'Naam (aflopend)',
            default => throw new InvalidArgumentException("Unsupported order by column: $column"),
        };

        $browser->waitFor('@selectControlOrderBy');
        $browser->click('@selectControlOrderBy .select-control-search');
        $this->findOptionElement($browser, '@selectControlOrderBy', $name)->click();

        $this->assertProviderVisible($browser, $provider, 2);
        $this->assertProviderVisible($browser, $provider2, 2);

        if ($dir === 'asc') {
            $browser->waitForTextIn($this->getWebshopRowsSelector() . ':nth-child(1)', $provider->name);
            $browser->waitForTextIn($this->getWebshopRowsSelector() . ':nth-child(2)', $provider2->name);
        } else {
            $browser->waitForTextIn($this->getWebshopRowsSelector() . ':nth-child(1)', $provider2->name);
            $browser->waitForTextIn($this->getWebshopRowsSelector() . ':nth-child(2)', $provider->name);
        }

        return $this;
    }

    /**
     * @param Browser $browser
     * @param Organization $provider
     * @throws TimeOutException
     * @return ProvidersSearchFilterTest
     */
    protected function assertProvidersSearchIsWorking(Browser $browser, Organization $provider): static
    {
        return $this
            ->assertSearch($browser, $provider, $provider->name)
            ->fillSearchForEmptyResults($browser)
            ->assertSearch($browser, $provider, $provider->email)
            ->fillSearchForEmptyResults($browser)
            ->assertSearch($browser, $provider, $provider->phone)
            ->fillSearchForEmptyResults($browser)
            ->assertSearch($browser, $provider, $provider->website)
            ->fillSearchForEmptyResults($browser)
            ->assertSearch($browser, $provider, $provider->offices[0]->address)
            ->fillSearchForEmptyResults($browser)
            ->assertSearch($browser, $provider, $provider->business_type->name);
    }

    /**
     * @param Browser $browser
     * @param Organization $provider
     * @param string $q
     * @throws TimeoutException
     * @return ProvidersSearchFilterTest
     */
    protected function assertSearch(Browser $browser, Organization $provider, string $q): static
    {
        $this->searchWebshopList($browser, '@listProviders', $q, $provider->id);
        $this->clearField($browser, '@listProvidersSearch');

        return $this;
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return ProvidersSearchFilterTest
     */
    protected function fillSearchForEmptyResults(Browser $browser): static
    {
        $this->searchWebshopList($browser, '@listProviders', '###############', null, 0);
        $this->clearField($browser, '@listProvidersSearch');

        return $this;
    }

    /**
     * @param Browser $browser
     * @param Organization $provider
     * @param int $count
     * @throws TimeoutException
     * @return ProvidersSearchFilterTest
     */
    protected function assertProviderVisible(Browser $browser, Organization $provider, int $count = 1): static
    {
        $browser->waitFor("@listProvidersRow$provider->id");
        $browser->assertVisible("@listProvidersRow$provider->id");
        $this->assertWebshopRowsCount($browser, $count, '@listProvidersContent');

        return $this;
    }
}
