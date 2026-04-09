<?php

namespace Browser;

use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Organization;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class FundTakenByPartnerTest extends DuskTestCase
{
    use WithFaker;
    use MakesTestFunds;
    use HasFrontendActions;
    use RollbackModelsTrait;
    use MakesTestFundRequests;
    use MakesTestOrganizations;

    /**
     * @throws Throwable
     */
    public function testFundTakenByPartnerPendingFundRequest()
    {
        $implementation = Implementation::byKey('nijmegen');
        $fund = $this->createFund($implementation->organization);

        $partnerWithPendingRequest = $this->makeIdentity($this->makeUniqueEmail());
        $requester = $this->makeIdentity($this->makeUniqueEmail(), 123456789);

        $this->rollbackModels([], function () use ($implementation, $fund, $partnerWithPendingRequest, $requester) {
            $this->browse(function (Browser $browser) use ($implementation, $fund, $partnerWithPendingRequest, $requester) {
                $browser->visit($implementation->urlWebshop())->waitFor('@headerTitle');
                $this->loginIdentity($browser, $partnerWithPendingRequest);

                // got to activate page and assert no taken_by_partner block present
                $browser->visit($implementation->urlWebshop("fondsen/$fund->id/activeer"));
                $browser->waitFor('@fundRequestOptions')->assertPresent('@fundRequestOptions');
                $browser->assertMissing('@takenByPartnerPendingFundRequest');

                $fundRequest = $this->makeFundRequestWithRecord($partnerWithPendingRequest, $fund, 'partner_bsn', 123456789);

                $this->logout($browser);

                // login requester
                $browser->visit($implementation->urlWebshop())->waitFor('@headerTitle');
                $this->loginIdentity($browser, $requester);

                // go to activate page as requester and assert taken_by_partner by pending fund request block is visible
                $browser->visit($implementation->urlWebshop("fondsen/$fund->id/activeer"));
                $browser->assertMissing('@fundRequestOptions');
                $browser->waitFor('@takenByPartnerPendingFundRequest');
                $browser->assertPresent('@takenByPartnerPendingFundRequest');

                // delete pending fund request and assert that no taken_by_partner block is visible
                $fundRequest->forceDelete();
                $browser->refresh();

                $browser->waitFor('@fundRequestOptions')->assertPresent('@fundRequestOptions');
                $browser->assertMissing('@takenByPartnerPendingFundRequest');

                // set partner_bsn record to partnerWithPendingRequest and create new fund request but without partner_bsn record
                // assert taken_by_partner by pending fund request block is visible
                $this->makeValidatedIdentityRecordForFund($partnerWithPendingRequest, $fund, 'partner_bsn', 123456789);
                $this->setCriteriaAndMakeFundRequest($partnerWithPendingRequest, $fund, ['children_nth' => 3]);

                $browser->refresh();
                $browser->assertMissing('@fundRequestOptions');
                $browser->waitFor('@takenByPartnerPendingFundRequest');
                $browser->assertPresent('@takenByPartnerPendingFundRequest');
            });
        }, function () use ($fund, $partnerWithPendingRequest, $requester) {
            $fund && $this->deleteFund($fund);
            $partnerWithPendingRequest->records()->delete();
            $requester->records()->delete();
        });
    }

    /**
     * @throws Throwable
     */
    public function testFundTakenByPartnerVoucher()
    {
        $implementation = Implementation::byKey('nijmegen');
        $fund = $this->createFund($implementation->organization);

        $partnerWithVoucher = $this->makeIdentity($this->makeUniqueEmail());
        $requester = $this->makeIdentity($this->makeUniqueEmail(), 123456789);

        $this->rollbackModels([], function () use ($implementation, $fund, $partnerWithVoucher, $requester) {
            $this->browse(function (Browser $browser) use ($implementation, $fund, $partnerWithVoucher, $requester) {
                $browser->visit($implementation->urlWebshop())->waitFor('@headerTitle');
                $this->loginIdentity($browser, $partnerWithVoucher);

                // got to activate page and assert no taken_by_partner block present
                $browser->visit($implementation->urlWebshop("fondsen/$fund->id/activeer"));
                $browser->waitFor('@fundRequestOptions')->assertPresent('@fundRequestOptions');
                $browser->assertMissing('@takenByPartnerVoucher');

                $fundRequest = $this->makeFundRequestWithRecord($partnerWithVoucher, $fund, 'partner_bsn', 123456789);

                // approve fund request and assert voucher created
                $employee = $fund->organization->employees()->first();
                $fundRequest->assignEmployee($employee)->approve();
                $this->assertSame(1, $fund->vouchers()->where('identity_id', $partnerWithVoucher->id)->count());

                $this->logout($browser);

                // login requester
                $browser->visit($implementation->urlWebshop())->waitFor('@headerTitle');
                $this->loginIdentity($browser, $requester);

                // go to activate page as requester and assert taken_by_partner by pending fund request block is visible
                $browser->visit($implementation->urlWebshop("fondsen/$fund->id/activeer"));
                $browser->assertMissing('@fundRequestOptions');
                $browser->waitFor('@takenByPartnerVoucher');
                $browser->assertPresent('@takenByPartnerVoucher');
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @param Organization $organization
     * @return Fund
     */
    protected function createFund(Organization $organization): Fund
    {
        return $this
            ->makeTestFund($organization, [], [
                'outcome_type' => 'voucher',
                'partner_deny' => true,
            ])
            ->syncCriteria([[
                'record_type_key' => 'children_nth',
                'operator' => '>',
                'value' => 2,
                'show_attachment' => false,
            ]])
            ->refresh();
    }
}
