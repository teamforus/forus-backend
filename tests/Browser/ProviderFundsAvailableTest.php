<?php

namespace Browser;

use App\Models\Fund;
use App\Models\Implementation;
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
        $fund = $this->makeTestFund(organization: $organization, fundConfigsData: [
            'allow_provider_sign_up' => true,
        ]);

        $fundTag = $this->faker->name;
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

                $this->goToProviderFundsList($browser);
                $this->goToFundsAvailableList($browser);

                // assert visible in list and filters
                $this->searchTable($browser, '@tableFundsAvailable', $fund->name, $fund->id);
                $browser->waitFor('@showFilters')->click('@showFilters');
                $this->assertOptionExistsInFilter($browser, '@selectControlImplementations', $implementation->name);
                $this->assertOptionExistsInFilter($browser, '@selectControlOrganizations', $implementation->organization->name);
                $this->assertOptionExistsInFilter($browser, '@selectControlTags', $fund->tags->first()->name);

                $fund->fund_config->update(['allow_provider_sign_up' => false]);

                $browser->refresh();
                $this->goToFundsAvailableList($browser);

                // assert missing in list and in filters
                $this->searchTable($browser, '@tableFundsAvailable', $fund->name, null, 0);
                $browser->click('@showFilters');
                $this->assertOptionExistsInFilter($browser, '@selectControlImplementations', $implementation->name, false);
                $this->assertOptionExistsInFilter($browser, '@selectControlOrganizations', $implementation->organization->name, false);
                $this->assertOptionExistsInFilter($browser, '@selectControlTags', $fund->tags->first()->name, false);

                // create another fund that allow provider sign_up - assert implementation and organization exist in filters
                $fund2 = $this->makeTestFund(organization: $implementation->organization, fundConfigsData: [
                    'allow_provider_sign_up' => true,
                ]);

                $browser->refresh();
                $this->goToFundsAvailableList($browser);

                $this->searchTable($browser, '@tableFundsAvailable', $fund->name, null, 0);
                $this->searchTable($browser, '@tableFundsAvailable', $fund2->name, $fund2->id);

                $browser->click('@showFilters');
                $this->assertOptionExistsInFilter($browser, '@selectControlImplementations', $implementation->name);
                $this->assertOptionExistsInFilter($browser, '@selectControlOrganizations', $implementation->organization->name);
                // assert tag from the first fund doesn't exist
                $this->assertOptionExistsInFilter($browser, '@selectControlTags', $fund->tags->first()->name, false);

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
     * @param string $selector
     * @param string $text
     * @param bool $exists
     * @throws TimeoutException
     * @throws \Facebook\WebDriver\Exception\ElementClickInterceptedException
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @return void
     */
    protected function assertOptionExistsInFilter(Browser $browser, string $selector, string $text, bool $exists = true): void
    {
        $browser->waitFor("{$selector}Toggle");

        $elements = $browser->elements($selector);
        if (count($elements) === 0 || !$elements[0]->isDisplayed()) {
            $browser->click("{$selector}Toggle");
        }

        $browser->waitFor($selector);
        $browser->click("$selector .select-control-search");
        $this->findOptionElement($browser, $selector, $text, $exists);
        $browser->click("{$selector}Toggle");
    }
}
