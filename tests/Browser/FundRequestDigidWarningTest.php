<?php

namespace Browser;

use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Organization;
use App\Services\DigIdService\Models\DigIdSession;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class FundRequestDigidWarningTest extends DuskTestCase
{
    use WithFaker;
    use MakesTestFunds;
    use HasFrontendActions;
    use MakesTestOrganizations;

    /**
     * @throws Throwable
     */
    public function testWebshopFundRequestDigidWarningAndExpired(): void
    {
        // Configure implementation and fund
        $implementation = Implementation::byKey('nijmegen');

        $this->assertNotNull($implementation);
        $this->assertNotNull($implementation->organization);
        $organization = $implementation->organization;

        // configure implementation and organization
        $implementationData = $implementation->only([
            'digid_enabled', 'digid_required', 'digid_connection_type', 'digid_app_id',
            'digid_shared_secret', 'digid_a_select_server',
        ]);

        $implementation->forceFill([
            'digid_enabled' => true,
            'digid_required' => true,
            'digid_connection_type' => DigIdSession::CONNECTION_TYPE_CGI,
            'digid_app_id' => 'test',
            'digid_shared_secret' => 'test',
            'digid_a_select_server' => 'test',
        ])->save();

        $organization->forceFill([
            'fund_request_resolve_policy' => Organization::FUND_REQUEST_POLICY_AUTO_REQUESTED,
        ])->save();

        $fund = $this->makeTestFund($organization, [
            'type' => 'budget',
        ], [
            'outcome_type' => 'voucher',
            'bsn_confirmation_time' => 20,
            'bsn_confirmation_api_time' => 30,
            'allow_fund_requests' => true,
            'allow_prevalidations' => true,
        ]);

        $this->makeFundCriteria($fund, [[
            'title' => 'Choose your municipality',
            'description' => 'Choose your municipality description',
            'record_type_key' => 'municipality',
            'operator' => '=',
            'value' => '268',
            'show_attachment' => false,
            'step' => 'Step #1',
        ], [
            'title' => 'Choose the number of children',
            'description' => 'Choose the number of children description',
            'record_type_key' => 'children_nth',
            'operator' => '>',
            'value' => 2,
            'show_attachment' => false,
            'step' => 'Step #1',
        ]]);

        $this->processFundRequestTestCase($implementation, $fund);

        $this->deleteFund($fund);
        $implementation->forceFill($implementationData)->save();
    }

    /**
     * @param Implementation $implementation
     * @param Fund $fund
     * @throws Throwable
     * @return void
     */
    protected function processFundRequestTestCase(Implementation $implementation, Fund $fund): void
    {
        $requester = $this->makeIdentity($this->makeUniqueEmail());

        $this->browse(function (Browser $browser) use (
            $implementation,
            $fund,
            $requester
        ) {
            $browser->visit($implementation->urlWebshop());
            $this->loginIdentity($browser, $requester);
            $browser->waitFor('@headerTitle');

            // visit fund page and assert request button available
            $browser->visit($implementation->urlWebshop("fondsen/$fund->id"));
            $browser->waitFor('@fundTitle');
            $browser->assertSeeIn('@fundTitle', $fund->name);

            // set a BSN for the requester to enable fund request option
            $requester->setBsnRecord('12345678');
            $browser->waitFor('@requestButton')->click('@requestButton');

            // select the DigID option and ensure the fund request form loads
            $browser->waitFor('@digidOption')->click('@digidOption');
            $browser->waitFor('@fundRequestForm');

            // verify the warning and expiration messages is not shown ahead of time
            $browser->assertMissing('@bsnWarning');
            $browser->assertMissing('@digidExpired');

            // time offset calculation for warning and expiration checks
            $timeOffset = $fund->fund_config->bsn_confirmation_time / 2;
            $timeBeforeReConfirmation = $fund->fund_config->bsn_confirmation_time;
            $timeBeforeWarning = $timeBeforeReConfirmation - $timeOffset;

            // verify the warning and expiration messages appear at the correct times
            $browser->waitFor('@bsnWarning', $timeBeforeWarning);
            $browser->waitFor('@digidExpired', $timeBeforeReConfirmation);

            // Logout user
            $this->logout($browser);
        });
    }
}
