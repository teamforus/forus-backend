<?php

namespace Tests\Browser;

use App\Models\FundProvider;
use App\Models\Implementation;
use App\Models\Product;
use App\Scopes\Builders\FundProviderQuery;
use Facebook\WebDriver\Exception\ElementClickInterceptedException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\NavigatesFrontendDashboard;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestIdentities;
use Throwable;

class ProductInformationalTest extends DuskTestCase
{
    use MakesTestFunds;
    use HasFrontendActions;
    use MakesTestIdentities;
    use RollbackModelsTrait;
    use NavigatesFrontendDashboard;

    /**
     * @throws Throwable
     * @return void
     */
    public function testCreateRegularProductInProviderDashboard(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $implementation = $this->makeTestImplementation($organization);
        $fund = $this->makeTestFund($organization);

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $provider = $this->makeTestProviderOrganization($identity);

        $this->rollbackModels([], function () use ($implementation, $fund, $identity, $provider) {
            $this->browse(function (Browser $browser) use ($implementation, $fund, $identity, $provider) {
                $browser->visit($implementation->urlProviderDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnProviderDashboard($browser, $identity);
                $this->selectDashboardOrganization($browser, $provider);

                $this->goToProviderProductsPage($browser);

                $browser->waitFor('@addProduct');
                $browser->click('@addProduct');

                $browser->waitFor('@productForm');
                $browser->waitFor('@nameInput');
                $browser->type('@nameInput', 'Product with regular price type');

                $this->fillDescription($browser);
                $this->fillCategories($browser);

                // set price type as regular
                $browser->waitFor('@selectControlPriceType');
                $this->changeSelectControl($browser, '@selectControlPriceType', index: 0);

                $browser->waitFor('@selectControlUnlimitedStock');
                $this->changeSelectControl($browser, '@selectControlUnlimitedStock', index: 1);

                // assert validation error while submit form without filling price
                $browser->click('@submitBtn');
                $this->assertAndCloseDangerNotification($browser);
                $browser->waitFor('@priceFormGroup @priceFormGroupError1');

                $browser->assertVisible('@priceInput');
                $browser->type('@priceInput', 10);

                $browser->assertVisible('@eanInput');
                $browser->assertVisible('@skuInput');

                $browser->click('@submitBtn');
                $this->assertAndCloseSuccessNotification($browser);

                $browser->waitFor('@productsTitle');

                /** @var Product $product */
                $product = $provider->products()->first();
                $this->assertNotNull($product);

                $this->searchTable($browser, '@tableProduct', $product->name, $product->id);

                // Logout
                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testCreateInformationalProductInProviderDashboard(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $implementation = $this->makeTestImplementation($organization);
        $fund = $this->makeTestFund($organization);

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $provider = $this->makeTestProviderOrganization($identity);

        $this->rollbackModels([], function () use ($implementation, $fund, $identity, $provider) {
            $this->browse(function (Browser $browser) use ($implementation, $fund, $identity, $provider) {
                $browser->visit($implementation->urlProviderDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnProviderDashboard($browser, $identity);
                $this->selectDashboardOrganization($browser, $provider);

                $this->goToProviderProductsPage($browser);

                $browser->waitFor('@addProduct');
                $browser->click('@addProduct');

                $browser->waitFor('@productForm');
                $browser->waitFor('@nameInput');
                $browser->type('@nameInput', 'Product with informational price type');

                $this->fillDescription($browser);
                $this->fillCategories($browser);

                // set price type as informational
                $browser->waitFor('@selectControlPriceType');
                $this->changeSelectControl($browser, '@selectControlPriceType', index: 4);

                // assert price, stock, etc. inputs are hidden
                $browser->assertMissing('@selectControlUnlimitedStock');
                $browser->assertMissing('@priceInput');
                $browser->assertMissing('@eanInput');
                $browser->assertMissing('@skuInput');

                $browser->click('@submitBtn');
                $this->assertAndCloseSuccessNotification($browser);

                $browser->waitFor('@productsTitle');

                /** @var Product $product */
                $product = $provider->products()->first();
                $this->assertNotNull($product);

                $this->searchTable($browser, '@tableProduct', $product->name, $product->id);

                // Logout
                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testInformationalProductOnWebshop(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $fund = $this->makeTestFund($organization);

        $provider = $this->makeTestProviderOrganization($this->makeIdentity($this->makeUniqueEmail()));

        $product = $this->makeTestProduct($provider, attributes: [
            'price' => 0,
            'price_type' => Product::PRICE_TYPE_INFORMATIONAL,
            'reservation_enabled' => 0,
        ]);

        $product->fund_providers()->firstOrCreate([
            'organization_id' => $provider->id,
            'fund_id' => $fund->id,
            'state' => FundProvider::STATE_ACCEPTED,
            'allow_budget' => true,
            'allow_products' => true,
        ]);

        /** @var \Illuminate\Database\Eloquent\Collection|FundProvider[] $fund_providers */
        $fund_providers = FundProviderQuery::whereApprovedForFundsFilter(
            FundProvider::query(),
            $fund->id,
        )->get();

        foreach ($fund_providers as $fund_provider) {
            $product->fund_provider_products()->create([
                'amount' => $product->price,
                'limit_total' => $product->unlimited_stock ? 1000 : $product->stock_amount,
                'fund_provider_id' => $fund_provider->id,
                'limit_per_identity' => $product->unlimited_stock ? 25 : ceil(max($product->stock_amount / 10, 1)),
            ]);
        }

        $this->rollbackModels([], function () use ($implementation, $fund, $provider, $product) {
            $this->browse(function (Browser $browser) use ($implementation, $fund, $provider, $product) {
                $browser->visit($implementation->urlWebshop('aanbod'));

                $this->filterByPriceTypeInformational($browser);

                $browser->waitFor('@listProductsContent');

                $this->assertInformationalProductInList($browser, $product, 'Grid');
                $this->assertInformationalProductInList($browser, $product, 'List');

                // assert on product page
                $browser->click("@listProductsRow$product->id");
                $browser->waitFor('@productName');
                $browser->assertSeeIn('@productName', $product->name);
                $browser->assertSeeIn('@productPrice', 'Alleen in winkel');
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @param Browser $browser
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeOutException
     * @return void
     */
    protected function filterByPriceTypeInformational(Browser $browser): void
    {
        $browser->waitFor('@productFilterGroupPriceType');
        $this->uncollapseWebshopFilterGroup($browser, '@productFilterGroupPriceType');

        $browser->waitFor('@priceTypeOptionInformational');
        $browser->click('@priceTypeOptionInformational');
    }

    /**
     * @param Browser $browser
     * @param Product $product
     * @param string $displayType
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    protected function assertInformationalProductInList(Browser $browser, Product $product, string $displayType): void
    {
        $browser->waitFor("@displayType{$displayType}Tab");
        $browser->click("@displayType{$displayType}Tab");
        $browser->waitFor("@displayType$displayType");

        $browser->waitFor("@listProductsRow$product->id");
        $browser->assertVisible("@listProductsRow$product->id");

        $browser->within("@listProductsRow$product->id", function (Browser $browser) use ($product) {
            $browser->assertSeeIn('@productName', $product->name);
            $browser->assertSeeIn('@productPrice', 'Alleen in winkel');
        });
    }

    /**
     * @param Browser $browser
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    protected function fillCategories(Browser $browser): void
    {
        foreach (range(0, 1) as $index) {
            $this->changeSelectControl($browser, "@selectControlProductCategory$index", index: 1);
        }
    }

    /**
     * @param Browser $browser
     * @return void
     */
    protected function fillDescription(Browser $browser): void
    {
        $browser->within('.note-editor', function ($browser) {
            $browser->click('.note-editable')->keys('.note-editable', 'Product description');
        });
    }
}
