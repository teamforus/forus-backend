<?php

namespace Tests\Browser;

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
use Tests\Traits\MakesTestIdentities;
use Throwable;

class ProviderFundsAvailableTest extends DuskTestCase
{
    use MakesTestFunds;
    use HasFrontendActions;
    use MakesTestIdentities;
    use RollbackModelsTrait;

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundAvailableVisibilityInProviderDashboard(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $implementation = $this->makeTestImplementation($organization);
        $fundTag = $this->faker->name;

        $fund = $this->makeTestFund(organization: $organization, fundConfigsData: [
            'allow_provider_sign_up' => true,
        ]);

        $tag = $fund->tags()->firstOrCreate([
            'key' => Str::slug($fundTag),
            'scope' => 'provider',
        ]);

        $tag->translateOrNew(app()->getLocale())->fill([
            'name' => $fundTag,
        ])->save();

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $provider = $this->makeTestProviderOrganization($identity);

        $this->rollbackModels([], function () use ($implementation, $fund, $identity, $provider) {
            $this->browse(function (Browser $browser) use ($implementation, $fund, $identity, $provider) {
                $browser->visit($implementation->urlProviderDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnProviderDashboard($browser, $identity);
                $this->selectDashboardOrganization($browser, $provider);

                $this->goToProviderFundsList($browser, 'funds_available');

                // assert visible in list and filters
                $this->assertFundAvailability($browser, $fund, available: true);
                $this->assertFundRelatedFiltersVisibility($browser, $fund, available: true);
                $this->assertOptionExistsInFilter($browser, '@selectControlTags', $fund->tags->first()->name);

                $fund->fund_config->update(['allow_provider_sign_up' => false]);

                $browser->refresh();
                // todo: remove when tab will be added to url
                $this->goToProviderFundsList($browser, 'funds_available', skipPageNavigation: true);

                // assert missing in list and in filters
                $this->assertFundAvailability($browser, $fund, available: false);
                $this->assertFundRelatedFiltersVisibility($browser, $fund, available: false);
                $this->assertOptionExistsInFilter($browser, '@selectControlTags', $fund->tags->first()->name, false);

                // create another fund that allow provider sign_up - assert implementation and organization exist in filters
                $fund2 = $this->makeTestFund(organization: $implementation->organization, fundConfigsData: [
                    'allow_provider_sign_up' => true,
                ]);

                $browser->refresh();
                // todo: remove when tab will be added to url
                $this->goToProviderFundsList($browser, 'funds_available', skipPageNavigation: true);

                $this->assertFundAvailability($browser, $fund, available: false);
                $this->assertFundAvailability($browser, $fund2, available: true);
                $this->assertFundRelatedFiltersVisibility($browser, $fund, available: true);

                // assert tag from the first fund doesn't exist
                $this->assertOptionExistsInFilter($browser, '@selectControlTags', $fund->tags->first()->name, available: false);

                // Logout
                $this->logout($browser);
                $this->deleteFund($fund2);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProviderSignUpLinkVisibility(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;
        $organization->funds->each(fn (Fund $fund) => $fund->fund_config->update(['allow_provider_sign_up' => false]));

        $fund = $this->makeTestFund(organization: $organization, fundConfigsData: [
            'allow_provider_sign_up' => false,
        ]);

        $this->makeProviderAndProducts($fund);

        $this->rollbackModels([], function () use ($implementation, $fund) {
            $this->browse(function (Browser $browser) use ($implementation, $fund) {
                $browser->visit($implementation->urlWebshop('providers'));
                // wait for fund loaded as a link depends on it
                $browser->waitFor('@selectControlFunds');
                $browser->assertMissing('@providerSignUpLink');

                $fund->fund_config->update(['allow_provider_sign_up' => true]);

                $browser->refresh();
                $browser->waitFor('@providerSignUpLink');
            });
        }, function () use ($fund, $organization) {
            $fund && $this->deleteFund($fund);
            $organization->funds->each(fn (Fund $fund) => $fund->fund_config->update(['allow_provider_sign_up' => true]));
        });
    }

    /**
     * @param Browser $browser
     * @param Fund $fund
     * @param bool $available
     * @throws TimeoutException
     * @return void
     */
    protected function assertFundAvailability(Browser $browser, Fund $fund, bool $available): void
    {
        $this->searchTable($browser, '@tableFundsAvailable', $fund->name, $fund->id, $available ? 1 : 0);
    }

    /**
     * @param Browser $browser
     * @param Fund $fund
     * @param bool $available
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeOutException
     * @return void
     */
    protected function assertFundRelatedFiltersVisibility(Browser $browser, Fund $fund, bool $available): void
    {
        $browser->waitFor('@showFilters')->click('@showFilters');
        $this->assertOptionExistsInFilter($browser, '@selectControlImplementations', $fund->getImplementation()->name, $available);
        $this->assertOptionExistsInFilter($browser, '@selectControlOrganizations', $fund->getImplementation()->organization->name, $available);
        $browser->waitFor('@showFilters')->click('@showFilters');
    }

    /**
     * @param Browser $browser
     * @param string $selector
     * @param string $text
     * @param bool $available
     * @throws \Facebook\WebDriver\Exception\ElementClickInterceptedException
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    protected function assertOptionExistsInFilter(
        Browser $browser,
        string $selector,
        string $text,
        bool $available = true,
    ): void {
        $browser->waitFor("{$selector}Toggle");

        $elements = $browser->elements($selector);
        if (count($elements) === 0 || !$elements[0]->isDisplayed()) {
            $browser->click("{$selector}Toggle");
        }

        $browser->waitFor($selector);
        $browser->click("$selector .select-control-search");
        $this->findOptionElement($browser, $selector, $text, $available);
        $browser->click("{$selector}Toggle");
    }
}
