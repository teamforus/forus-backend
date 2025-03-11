<?php

namespace Tests\Browser;

use App\Models\Fund;
use App\Models\Implementation;
use App\Services\DigIdService\Models\DigIdSession;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class FundRequestOptionsTest extends DuskTestCase
{
    use WithFaker;
    use MakesTestFunds;
    use HasFrontendActions;
    use MakesTestFundRequests;
    use MakesTestOrganizations;
    use RollbackModelsTrait;

    /**
     * Test that when only the Digid feature is enabled, the fund request page shows
     * only the Digid option and hides the code and request options.
     *
     * @throws Throwable
     */
    public function testWebshopFundRequestOptionsOnlyDigidOptionAvailable(): void
    {
        $implementation = Implementation::byKey('nijmegen');

        $fund = $this->makeTestFund($implementation->organization, [
            'type' => 'budget',
        ], [
            'bsn_confirmation_time' => 900,
            'bsn_confirmation_api_time' => 900,
            'allow_fund_requests' => true,
            'allow_prevalidations' => false,
        ]);

        // Temporarily change the implementation settings.
        $this->rollbackModels([
            [$implementation, $implementation->only(['digid_enabled', 'digid_required'])],
        ], function () use ($implementation, $fund) {
            $implementation->forceFill([
                'digid_enabled' => true,
                'digid_required' => true,
                'digid_connection_type' => DigIdSession::CONNECTION_TYPE_CGI,
                'digid_app_id' => 'test',
                'digid_shared_secret' => 'test',
                'digid_a_select_server' => 'test',
            ])->save();

            $this->assertFundRequestOptionsVisibility(
                $fund,
                ['@digidOption'],
                ['@codeOption', '@requestOption'],
            );
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * Test that when both prevalidations and Digid are enabled, while fund requests are disabled
     * the fund request page shows the Digid and Code options, and hides the Request option.
     *
     * @throws Throwable
     */
    public function testWebshopFundRequestOptionsOnlyDigidAndCodeOptionsAvailable(): void
    {
        $implementation = Implementation::byKey('nijmegen');

        $fund = $this->makeTestFund($implementation->organization, [
            'type' => 'budget',
        ], [
            'bsn_confirmation_time' => 900,
            'bsn_confirmation_api_time' => 900,
            'allow_fund_requests' => true,
            'allow_prevalidations' => true,
        ]);

        // Temporarily change the implementation settings.
        $this->rollbackModels([
            [$implementation, $implementation->only(['digid_enabled', 'digid_required'])],
        ], function () use ($implementation, $fund) {
            $implementation->forceFill([
                'digid_enabled' => true,
                'digid_required' => true,
                'digid_connection_type' => DigIdSession::CONNECTION_TYPE_CGI,
                'digid_app_id' => 'test',
                'digid_shared_secret' => 'test',
                'digid_a_select_server' => 'test',
            ])->save();

            $this->assertFundRequestOptionsVisibility(
                $fund,
                ['@digidOption', '@codeOption'],
                ['@requestOption'],
            );
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * Test that when Digid requirement is disabled and only request option is available,
     * the request option is auto-selected and the criteria steps overview is shown.
     *
     * @throws Throwable
     */
    public function testWebshopFundRequestOptionsSoleRequestOptionIsAutoSelected(): void
    {
        $implementation = Implementation::byKey('nijmegen');

        $fund = $this->makeTestFund($implementation->organization, [
            'type' => 'budget',
        ], [
            'allow_fund_requests' => true,
            'allow_prevalidations' => false,
        ]);

        // Temporarily change the implementation settings.
        $this->rollbackModels([
            [$implementation, $implementation->only(['digid_enabled', 'digid_required'])],
        ], function () use ($implementation, $fund) {
            $implementation->forceFill([
                'digid_enabled' => false,
                'digid_required' => false,
            ])->save();

            // When only @requestOption is available, it should be auto-selected,
            // and @criteriaStepsOverview displayed.
            $this->assertFundRequestOptionsVisibility(
                $fund,
                ['@criteriaStepsOverview'],
                ['@codeOption', '@digidOption'],
            );
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * Test that when Digid is not required and prevalidations are allowed,
     * the fund request page shows the request and code options while hiding the Digid option.
     *
     * @throws Throwable
     */
    public function testWebshopFundRequestOptionsOnlyRequestAndCodeOptionsAvailable(): void
    {
        $implementation = Implementation::byKey('nijmegen');

        $fund = $this->makeTestFund($implementation->organization, [
            'type' => 'budget',
        ], [
            'allow_fund_requests' => true,
            'allow_prevalidations' => true,
        ]);

        // Temporarily change the implementation settings.
        $this->rollbackModels([
            [$implementation, $implementation->only(['digid_enabled', 'digid_required'])],
        ], function () use ($implementation, $fund) {
            $implementation->forceFill([
                'digid_enabled' => false,
                'digid_required' => false,
            ])->save();

            $this->assertFundRequestOptionsVisibility(
                $fund,
                ['@requestOption', '@codeOption'],
                ['@digidOption'],
            );
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * This helper method checks that the correct options appear on the fund request page.
     *
     * @param Fund $fund The fund being tested.
     * @param array $present List of element selectors that should appear.
     * @param array $missing List of element selectors that should not appear.
     * @throws Throwable
     * @return void
     */
    protected function assertFundRequestOptionsVisibility(
        Fund $fund,
        array $present,
        array $missing,
    ): void {
        $this->browse(function (Browser $browser) use ($fund, $present, $missing) {
            $requester = $this->makeIdentity($this->makeUniqueEmail());
            $implementation = $fund->getImplementation();

            $browser->visit($implementation->urlWebshop());

            $this->loginIdentity($browser, $requester);
            $browser->waitFor('@headerTitle');

            // visit fund page and assert request button available
            $browser->visit($implementation->urlWebshop("fondsen/$fund->id"));
            $browser->waitFor('@fundTitle');
            $browser->assertSeeIn('@fundTitle', $fund->name);

            // assert request button available
            $browser->waitFor('@requestButton')->click('@requestButton');

            foreach ($present as $selector) {
                $browser->waitFor($selector)->assertPresent($selector);
            }

            foreach ($missing as $selector) {
                $browser->assertMissing($selector);
            }

            // Logout user
            $this->logout($browser);
        });
    }
}
