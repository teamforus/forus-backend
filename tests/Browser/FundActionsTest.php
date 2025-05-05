<?php

namespace Browser;

use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Organization;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class FundActionsTest extends DuskTestCase
{
    use WithFaker;
    use MakesTestFunds;
    use HasFrontendActions;
    use MakesTestFundRequests;
    use MakesTestOrganizations;

    /**
     * @throws Throwable
     */
    public function testFundDetailsActions(string $type = 'voucher')
    {
        // Select implementation
        $implementation = Implementation::byKey('nijmegen');
        $this->assertTrue(in_array($type, ['voucher', 'payout']));

        $fundConfigs = match ($type) {
            'voucher' => $this->getVoucherFundSettings(),
            'payout' => $this->getPayoutFundSettings(),
        };

        $this->browse(function (Browser $browser) use ($implementation, $fundConfigs, $type) {
            $requester = $this->makeIdentity($this->makeUniqueEmail());
            $fund = $this->createFund($implementation->organization, $fundConfigs);

            $browser->visit($implementation->urlWebshop())->waitFor('@headerTitle');
            $this->loginIdentity($browser, $requester);

            $browser->visit($implementation->urlWebshop("fondsen/$fund->id"));
            $browser->waitFor('@fundTitle')->assertSeeIn('@fundTitle', $fund->name);

            // assert only fund request button present
            $browser->assertPresent('@requestButton');
            $browser->assertMissing('@activateButton');
            $browser->assertMissing('@payoutsButton');
            $browser->assertMissing('@voucherButton');
            $browser->assertMissing('@pendingButton');

            // create a fund request and assert only fund request pending button present
            $fundRequest = $this->setCriteriaAndMakeFundRequest($requester, $fund, $fundConfigs['requester_records']);

            $browser->refresh();
            $browser->waitFor('@fundTitle')->assertSeeIn('@fundTitle', $fund->name);
            $browser->assertMissing('@requestButton');
            $browser->assertMissing('@activateButton');
            $browser->assertMissing('@payoutsButton');
            $browser->assertMissing('@voucherButton');
            $browser->assertPresent('@pendingButton');

            // approve fund request and assert only voucher or payout button present
            $this->approveFundRequest($fundRequest);

            $browser->refresh();
            $browser->waitFor('@fundTitle')->assertSeeIn('@fundTitle', $fund->name);
            $browser->assertMissing('@requestButton');
            $browser->assertMissing('@activateButton');
            $browser->assertMissing($type === 'voucher' ? '@payoutsButton' : '@voucherButton');
            $browser->assertPresent($type === 'voucher' ? '@voucherButton' : '@payoutsButton');
            $browser->assertMissing('@pendingButton');

            // Create fund with same criteria
            $fund2 = $this->createFund($implementation->organization, $fundConfigs);
            $browser->refresh();

            // Assert only activate button is present
            $browser->visit($implementation->urlWebshop("fondsen/$fund2->id"));
            $browser->waitFor('@fundTitle')->assertSeeIn('@fundTitle', $fund2->name);
            $browser->assertMissing('@requestButton');
            $browser->assertPresent('@activateButton');
            $browser->assertMissing('@payoutsButton');
            $browser->assertMissing('@voucherButton');
            $browser->assertMissing('@pendingButton');

        });
    }

    /**
     * @throws Throwable
     */
    public function testFundDetailsActionsOnPayoutFund()
    {
        $this->testFundDetailsActions('payout');
    }

    /**
     * @throws Throwable
     */
    public function testFundsListActions(string $type = 'voucher')
    {
        // Select implementation
        $implementation = Implementation::byKey('nijmegen');
        $this->assertTrue(in_array($type, ['voucher', 'payout']));

        $fundConfigs = match ($type) {
            'voucher' => $this->getVoucherFundSettings(),
            'payout' => $this->getPayoutFundSettings(),
        };

        $this->browse(function (Browser $browser) use ($implementation, $fundConfigs, $type) {
            $requester = $this->makeIdentity($this->makeUniqueEmail());
            $fund = $this->createFund($implementation->organization, $fundConfigs);

            $browser->visit($implementation->urlWebshop())->waitFor('@headerTitle');
            $this->loginIdentity($browser, $requester);

            $browser->visit($implementation->urlWebshop('fondsen'));
            $browser->waitFor('@fundsSearchInput')->type('@fundsSearchInput', $fund->name);
            $browser->waitFor("@fundItem$fund->id")->assertMissing("@fundItem$fund->id @pendingButton");
            $browser->waitFor("@fundItem$fund->id")->assertMissing("@fundItem$fund->id @activateButton");

            // Create a fund request and assert only fund request pending button present
            $fundRequest = $this->setCriteriaAndMakeFundRequest($requester, $fund, $fundConfigs['requester_records']);

            $browser->refresh();
            $browser->waitFor('@fundsSearchInput')->type('@fundsSearchInput', $fund->name);
            $browser->waitFor("@fundItem$fund->id")->assertPresent("@fundItem$fund->id @pendingButton");
            $browser->waitFor("@fundItem$fund->id")->assertMissing("@fundItem$fund->id @activateButton");

            // Approve fund request and assert only voucher or payout button present
            $this->approveFundRequest($fundRequest);

            $browser->refresh();
            $browser->waitFor('@fundsSearchInput')->type('@fundsSearchInput', $fund->name);
            $browser->waitFor("@fundItem$fund->id")->assertMissing("@fundItem$fund->id @pendingButton");
            $browser->waitFor("@fundItem$fund->id")->assertMissing("@fundItem$fund->id @activateButton");

            // Create fund with same criteria
            $fund2 = $this->createFund($implementation->organization, $fundConfigs);
            $browser->refresh();

            // Assert activate button is shown due to valid records from previous fund
            $browser->visit($implementation->urlWebshop('fondsen'));
            $browser->waitFor('@fundsSearchInput')->type('@fundsSearchInput', $fund2->name);
            $browser->waitFor("@fundItem$fund2->id")->assertMissing("@fundItem$fund2->id @pendingButton");
            $browser->waitFor("@fundItem$fund2->id")->assertPresent("@fundItem$fund2->id @activateButton");
        });
    }

    /**
     * @throws Throwable
     */
    public function testFundsListActionsOnPayoutFund()
    {
        $this->testFundsListActions('payout');
    }

    /**
     * @throws Throwable
     */
    public function testFundActivatePageActions(string $type = 'voucher')
    {
        // Select implementation
        $implementation = Implementation::byKey('nijmegen');
        $this->assertTrue(in_array($type, ['voucher', 'payout']));

        $fundConfigs = match ($type) {
            'voucher' => $this->getVoucherFundSettings(),
            'payout' => $this->getPayoutFundSettings(),
        };

        $this->browse(function (Browser $browser) use ($implementation, $fundConfigs, $type) {
            $requester = $this->makeIdentity($this->makeUniqueEmail());
            $fund = $this->createFund($implementation->organization, $fundConfigs);

            $browser->visit($implementation->urlWebshop())->waitFor('@headerTitle');
            $this->loginIdentity($browser, $requester);

            // Fund activate page: assert pending fund request shown
            $browser->visit($implementation->urlWebshop("fondsen/$fund->id/activeer"));
            $browser->waitFor('@fundRequestOptions')->assertPresent('@fundRequestOptions');
            $browser->assertMissing('@approvedFundRequest');
            $browser->assertMissing('@existingFundRequest');

            // create a fund request and assert only fund request pending button present
            $fundRequest = $this->setCriteriaAndMakeFundRequest($requester, $fund, $fundConfigs['requester_records']);

            $browser->refresh();
            $browser->waitFor('@existingFundRequest')->assertPresent('@existingFundRequest');
            $browser->assertMissing('@approvedFundRequest');
            $browser->assertMissing('@fundRequestOptions');

            // approve fund request and assert only voucher or payout button present
            $this->approveFundRequest($fundRequest);

            $browser->refresh();
            $browser->waitFor('@approvedFundRequest')->assertPresent('@approvedFundRequest');
            $browser->assertMissing('@existingFundRequest');
            $browser->assertMissing('@fundRequestOptions');

            // Create fund with same criteria
            $fund2 = $this->createFund($implementation->organization, $fundConfigs);
            $browser->refresh();

            // assert voucher received and redirected to voucher details
            $browser
                ->visit($implementation->urlWebshop("fondsen/$fund2->id/activeer"))
                ->waitFor('@voucherTitle')
                ->assertSee($fund2->name);
        });
    }

    /**
     * @throws Throwable
     */
    public function testFundActivatePageActionsOnPayoutFund()
    {
        $this->testFundActivatePageActions('payout');
    }

    /**
     * @throws Throwable
     */
    public function testFundActivatePageWithFundRequestsFromPreviousPeriods(string $type = 'voucher')
    {
        $this->travelTo('2020-01-01');

        // Select implementation
        $implementation = Implementation::byKey('nijmegen');
        $this->assertTrue(in_array($type, ['voucher', 'payout']));

        $fundConfigs = match ($type) {
            'voucher' => $this->getVoucherFundSettings(),
            'payout' => $this->getPayoutFundSettings(),
        };

        $requester = $this->makeIdentity($this->makeUniqueEmail());
        $fund = $this->createFund($implementation->organization, $fundConfigs);

        $fundRequest = $this->setCriteriaAndMakeFundRequest($requester, $fund, $fundConfigs['requester_records']);
        $this->approveFundRequest($fundRequest);

        $this->travelBack();

        $fund->update([
            'end_date' => now()->addYear(),
        ]);

        $this->browse(function (Browser $browser) use ($implementation, $fund, $requester) {
            $browser->visit($implementation->urlWebshop())->waitFor('@headerTitle');
            $this->loginIdentity($browser, $requester);

            // Fund activate page: assert pending fund request shown
            $browser->visit($implementation->urlWebshop("fondsen/$fund->id/activeer"));
            $browser->waitFor('@fundRequestOptions')->assertPresent('@fundRequestOptions');
            $browser->assertMissing('@approvedFundRequest');
            $browser->assertMissing('@existingFundRequest');
        });
    }

    /**
     * @throws Throwable
     */
    public function testFundActivatePageWithFundRequestsFromPreviousPeriodsOnPayoutFund()
    {
        $this->testFundActivatePageWithFundRequestsFromPreviousPeriods('payout');
    }

    /**
     * @param Browser $browser
     * @param int $count
     * @param string $selector
     * @return void
     */
    protected function assertRowsCount(Browser $browser, int $count, string $selector): void
    {
        $browser->within($selector, function (Browser $browser) use ($count) {
            $browser->assertSeeIn('@paginatorTotal', $count);
            $this->assertCount(1, $browser->elements('tr>td'));
        });
    }

    /**
     * @param Organization $organization
     * @param array $settings
     * @return Fund
     */
    protected function createFund(Organization $organization, array $settings): Fund
    {
        return $this
            ->makeTestFund($organization, [], $settings['fund_config'])
            ->syncCriteria($settings['fund_criteria'] ?? [])
            ->refresh();
    }

    /**
     * @return array
     */
    protected function getPayoutFundSettings(): array
    {
        return [
            'fund_config' => [
                'outcome_type' => 'payout',
                'iban_record_key' => 'iban',
                'iban_name_record_key' => 'iban_name',
            ],
            'fund_criteria' => [[
                'record_type_key' => 'children_nth',
                'operator' => '>',
                'value' => 2,
                'show_attachment' => false,
            ], [
                'record_type_key' => 'iban',
                'operator' => '*',
                'value' => '',
                'show_attachment' => false,
            ], [
                'record_type_key' => 'iban_name',
                'operator' => '*',
                'value' => '',
                'show_attachment' => false,
            ]],
            'requester_records' => [
                'iban' => $this->faker->iban(),
                'iban_name' => $this->faker->firstName(),
                'children_nth' => 3,
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getVoucherFundSettings(): array
    {
        return [
            'fund_config' => [
                'outcome_type' => 'voucher',
            ],
            'fund_criteria' => [[
                'record_type_key' => 'children_nth',
                'operator' => '>',
                'value' => 2,
                'show_attachment' => false,
            ]],
            'requester_records' => [
                'children_nth' => 3,
            ],
        ];
    }

    /**
     * @param FundRequest $fundRequest
     * @return void
     */
    private function approveFundRequest(FundRequest $fundRequest): void
    {
        $employee = $fundRequest->fund->organization->employees[0];
        $this->assertNotNull($employee);

        $fundRequest->assignEmployee($employee);
        $fundRequest->refresh();

        $fundRequest->approve();
        $fundRequest->refresh();
    }
}
