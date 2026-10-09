<?php

namespace Tests\Browser;

use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Organization;
use Facebook\WebDriver\Exception\TimeOutException;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\NavigatesFrontendDashboard;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestIdentities;
use Tests\Traits\MakesTestOrganizationOffices;
use Throwable;

class ProviderFundsApplyTest extends DuskTestCase
{
    use MakesTestFunds;
    use HasFrontendActions;
    use MakesTestIdentities;
    use RollbackModelsTrait;
    use NavigatesFrontendDashboard;
    use MakesTestOrganizationOffices;

    /**
     * Provider finds a fund by search in the available tab and applies for it using the row button.
     *
     * @throws Throwable
     * @return void
     */
    public function testProviderAppliesForFund(): void
    {
        $sponsor = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $implementation = $this->makeTestImplementation($sponsor);
        $fund = $this->makeTestFund($sponsor, fundConfigsData: ['allow_provider_sign_up' => true]);

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $provider = $this->makeTestProviderOrganization($identity);
        $this->makeOrganizationOffice($provider);

        $this->rollbackModels([], function () use ($implementation, $fund, $identity, $provider) {
            $this->browse(function (Browser $browser) use ($implementation, $fund, $identity, $provider) {
                $browser->visit($implementation->urlProviderDashboard());

                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnProviderDashboard($browser, $identity);
                $this->selectDashboardOrganization($browser, $provider);

                // find the fund in the available tab
                $this->goToProviderFundsPage($browser, 'funds_available');
                $this->assertFundAvailable($browser, $fund, available: true);

                // apply for the fund
                $browser->click("@btnFundAvailableApply$fund->id");
                $this->assertAndCloseAppliedModal($browser);

                $fundProvider = $this->assertFundProviderPending($provider, $fund);

                // the fund must be listed in the pending tab (open the tab explicitly instead of relying on
                // the automatic tab switch after applying, which can be overridden by the filters reset on slow envs)
                $this->goToProviderFundsPage($browser, 'funds_pending');
                $this->assertPendingFundVisible($browser, $fundProvider);

                // the fund must not be available to apply for anymore
                $this->goToProviderFundsPage($browser, 'funds_available');
                $this->assertFundAvailable($browser, $fund, available: false);

                $this->assertTrue(
                    $fund->logs()->where('event', Fund::EVENT_PROVIDER_APPLIED)->exists(),
                    'Fund provider applied event log must be created.',
                );

                // Logout
                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * Provider finds multiple funds by search and applies for multiple funds at once.
     *
     * @throws Throwable
     * @return void
     */
    public function testProviderAppliesForMultipleFunds(): void
    {
        $sponsor = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $implementation = $this->makeTestImplementation($sponsor);

        // shared unique name prefix, to find both funds by search
        $namePrefix = 'Fund ' . Str::random();
        $fundConfigsData = ['allow_provider_sign_up' => true];

        $fund1 = $this->makeTestFund($sponsor, ['name' => "$namePrefix A"], $fundConfigsData);
        $fund2 = $this->makeTestFund($sponsor, ['name' => "$namePrefix B"], $fundConfigsData);

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $provider = $this->makeTestProviderOrganization($identity);
        $this->makeOrganizationOffice($provider);

        $this->rollbackModels([], function () use (
            $implementation,
            $namePrefix,
            $fund1,
            $fund2,
            $identity,
            $provider
        ) {
            $this->browse(function (Browser $browser) use (
                $implementation,
                $namePrefix,
                $fund1,
                $fund2,
                $identity,
                $provider
            ) {
                $browser->visit($implementation->urlProviderDashboard());

                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnProviderDashboard($browser, $identity);
                $this->selectDashboardOrganization($browser, $provider);

                $this->goToProviderFundsPage($browser, 'funds_available');

                $browser->waitFor('@tableFundsAvailableSearch');
                $this->typeSearchInput($browser, '@tableFundsAvailableSearch', $namePrefix);

                $browser->waitFor("@tableFundsAvailableRow$fund1->id");
                $browser->waitFor("@tableFundsAvailableRow$fund2->id");
                $this->assertRowsCount($browser, 2, '@tableFundsAvailableContent');

                // select both funds and apply in bulk
                $browser->assertMissing('@btnFundsAvailableApplySelected');
                $browser->click("@tableFundsAvailableCheckbox$fund1->id");
                $browser->click("@tableFundsAvailableCheckbox$fund2->id");

                $browser->waitFor('@btnFundsAvailableApplySelected');
                $browser->click('@btnFundsAvailableApplySelected');
                $this->assertAndCloseAppliedModal($browser);

                $fundProvider1 = $this->assertFundProviderPending($provider, $fund1);
                $fundProvider2 = $this->assertFundProviderPending($provider, $fund2);

                // both funds must be listed in the pending tab
                $this->goToProviderFundsPage($browser, 'funds_pending');
                $this->assertPendingFundVisible($browser, $fundProvider1);
                $this->assertPendingFundVisible($browser, $fundProvider2);

                // Logout
                $this->logout($browser);
            });
        }, function () use ($fund1, $fund2) {
            $fund1 && $this->deleteFund($fund1);
            $fund2 && $this->deleteFund($fund2);
        });
    }

    /**
     * Provider without offices can't apply for a fund.
     *
     * @throws Throwable
     * @return void
     */
    public function testProviderWithoutOfficesCanNotApplyForFund(): void
    {
        $sponsor = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $implementation = $this->makeTestImplementation($sponsor);
        $fund = $this->makeTestFund($sponsor, fundConfigsData: ['allow_provider_sign_up' => true]);

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $provider = $this->makeTestProviderOrganization($identity);

        $this->rollbackModels([], function () use ($implementation, $fund, $identity, $provider) {
            $this->browse(function (Browser $browser) use ($implementation, $fund, $identity, $provider) {
                $browser->visit($implementation->urlProviderDashboard());

                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnProviderDashboard($browser, $identity);
                $this->selectDashboardOrganization($browser, $provider);

                $this->goToProviderFundsPage($browser, 'funds_available');
                $this->assertFundAvailable($browser, $fund, available: true);

                // try to apply, the "no offices" modal must be shown instead
                $browser->click("@btnFundAvailableApply$fund->id");

                $browser->waitFor('@modalProviderFundApplyNoOffices');
                $browser->within('@modalProviderFundApplyNoOffices', fn (Browser $b) => $b->click('@cancelBtn'));
                $browser->waitUntilMissing('@modalProviderFundApplyNoOffices');

                // the fund is still available and no fund provider was created
                $browser->assertVisible('@tableFundsAvailableContent');
                $browser->assertVisible("@tableFundsAvailableRow$fund->id");

                $this->assertFalse(
                    $provider->fund_providers()->where('fund_id', $fund->id)->exists(),
                    'Fund provider must not be created for provider without offices.',
                );

                // Logout
                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @param Browser $browser
     * @param Fund $fund
     * @param bool $available
     * @throws TimeoutException
     * @return void
     */
    protected function assertFundAvailable(Browser $browser, Fund $fund, bool $available): void
    {
        if (!$available) {
            $browser->waitFor('@tableFundsAvailableSearch');
            $this->typeSearchInput($browser, '@tableFundsAvailableSearch', $fund->name);
            $browser->waitFor('@tableFundsAvailableContent @emptyCard');
            $browser->assertMissing("@tableFundsAvailableRow$fund->id");

            return;
        }

        $this->searchTable(
            $browser,
            selector: '@tableFundsAvailable',
            value: $fund->name,
            id: $fund->id,
        );
    }

    /**
     * @param Browser $browser
     * @param FundProvider $fundProvider
     * @throws TimeoutException
     * @return void
     */
    protected function assertPendingFundVisible(Browser $browser, FundProvider $fundProvider): void
    {
        $this->searchTable(
            $browser,
            selector: '@pending_rejectedTableFunds',
            value: $fundProvider->fund->name,
            id: $fundProvider->id,
        );
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return void
     */
    protected function assertAndCloseAppliedModal(Browser $browser): void
    {
        $browser->waitFor('@modalProviderFundApplied');
        $browser->within('@modalProviderFundApplied', fn (Browser $b) => $b->click('@submitBtn'));
        $browser->waitUntilMissing('@modalProviderFundApplied');
    }

    /**
     * @param Organization $provider
     * @param Fund $fund
     * @return FundProvider
     */
    protected function assertFundProviderPending(Organization $provider, Fund $fund): FundProvider
    {
        $fundProvider = $provider->fund_providers()->where('fund_id', $fund->id)->first();

        $this->assertNotNull($fundProvider, 'Fund provider must be created after applying.');
        $this->assertEquals(FundProvider::STATE_PENDING, $fundProvider->state);

        return $fundProvider;
    }
}
