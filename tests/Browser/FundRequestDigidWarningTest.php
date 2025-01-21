<?php

namespace Browser;

use App\Models\Implementation;
use App\Services\DigIdService\Models\DigIdSession;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\DuskTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;

class FundRequestDigidWarningTest extends DuskTestCase
{
    use WithFaker;
    use MakesTestFunds;
    use HasFrontendActions;
    use MakesTestOrganizations;

    /** @var array|array[] */
    public static array $applyDigidTestCase = [
        'implementation' => [
            'key' => 'nijmegen',
            'digid_enabled' => true,
            'digid_required' => true,
            'digid_connection_type' => DigIdSession::CONNECTION_TYPE_CGI,
            'digid_app_id' => 'test',
            'digid_shared_secret' => 'test',
            'digid_a_select_server' => 'test',
        ],
        'fund' => [
            'type' => 'budget',
            'criteria_editable_after_start' => true,
        ],
        'fund_config' => [
            'outcome_type' => 'voucher',
            'auth_2fa_restrict_emails' => true,
            'auth_2fa_restrict_auth_sessions' => true,
            'auth_2fa_restrict_reimbursements' => true,
            'custom_amount_min' => 100,
            'custom_amount_max' => 200,
            'allow_custom_amounts' => true,
            'allow_custom_amounts_validator' => true,
            'allow_preset_amounts' => true,
            'allow_preset_amounts_validator' => true,
            'bsn_confirmation_time' => 20,
            'bsn_confirmation_api_time' => 30,
            'allow_direct_requests' => true,
            'allow_fund_requests' => true,
            'allow_prevalidations' => true,
        ],
        'fund_criteria' => [[
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
        ]],
    ];

    /**
     * @throws \Throwable
     */
    public function testWebshopFundRequestApplyOptionDigid(): void
    {
        $this->processFundRequestTestCase(self::$applyDigidTestCase);
    }

    /**
     * @param array $testCase
     * @return void
     * @throws \Throwable
     */
    protected function processFundRequestTestCase(array $testCase): void
    {
        // Configure implementation and fund
        $implementation = Implementation::byKey($testCase['implementation']['key']);

        $this->assertNotNull($implementation);
        $this->assertNotNull($implementation->organization);

        $implementationData = $implementation->only(array_keys($testCase['implementation']));
        $implementation->forceFill($testCase['implementation'])->save();

        $requester = $this->makeIdentity($this->makeUniqueEmail());
        $fund = $this->makeTestFundAndConfigureForFundRequest($implementation->organization, $testCase);

        $this->browse(function (Browser $browser) use (
            $implementation, $fund, $requester, $testCase
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
            $timeOffset = $testCase['fund_config']['bsn_confirmation_time'] / 2;
            $timeBeforeReConfirmation = $testCase['fund_config']['bsn_confirmation_time'];
            $timeBeforeWarning = $timeBeforeReConfirmation - $timeOffset;

            // verify the warning and expiration messages appear at the correct times
            $browser->waitFor('@bsnWarning', $timeBeforeWarning);
            $browser->waitFor('@digidExpired', $timeBeforeReConfirmation);

            // Logout user
            $this->logout($browser);
        });

        // clean up test-created data and restore the implementation state
        $fund->criteria()->delete();
        $fund->criteria_steps()->delete();
        $fund->delete();

        $implementation->forceFill($implementationData)->save();
    }
}
