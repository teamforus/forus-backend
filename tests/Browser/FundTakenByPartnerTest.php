<?php

namespace Browser;

use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\RecordType;
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

        $requester1 = $this->makeIdentity($this->makeUniqueEmail());
        $requester2 = $this->makeIdentity($this->makeUniqueEmail(), 123456789);

        $this->rollbackModels([], function () use ($implementation, $fund, $requester1, $requester2) {
            $this->browse(function (Browser $browser) use ($implementation, $fund, $requester1, $requester2) {
                $browser->visit($implementation->urlWebshop())->waitFor('@headerTitle');
                $this->loginIdentity($browser, $requester1);

                // got to activate page and assert no taken_by_partner block present
                $browser->visit($implementation->urlWebshop("fondsen/$fund->id/activeer"));
                $browser->waitFor('@fundRequestOptions')->assertPresent('@fundRequestOptions');
                $browser->assertMissing('@takenByPartnerPendingFundRequest');

                // create pending fund request for requester1
                $fundRequest = $this->setCriteriaAndMakeFundRequest($requester1, $fund, [
                    'children_nth' => 3,
                ]);

                // add partner_bsn record to this fund request
                $fundRequest->records()->create([
                    'record_type_key' => 'partner_bsn',
                    'value' => 123456789,
                ]);

                $this->logout($browser);

                // login requester2
                $browser->visit($implementation->urlWebshop())->waitFor('@headerTitle');
                $this->loginIdentity($browser, $requester2);

                // go to activate page as requester2 and assert taken_by_partner by pending fund request block is visible
                $browser->visit($implementation->urlWebshop("fondsen/$fund->id/activeer"));
                $browser->assertMissing('@fundRequestOptions');
                $browser->waitFor('@takenByPartnerPendingFundRequest');
                $browser->assertPresent('@takenByPartnerPendingFundRequest');

                // delete pending fund request and assert that no taken_by_partner block is visible
                $fundRequest->forceDelete();
                $browser->refresh();

                $browser->waitFor('@fundRequestOptions')->assertPresent('@fundRequestOptions');
                $browser->assertMissing('@takenByPartnerPendingFundRequest');

                // set partner_bsn record to requester1 and create new fund request but without partner_bsn record
                // assert taken_by_partner by pending fund request block is visible
                $requester1
                    ->makeRecord(RecordType::where('key', 'partner_bsn')->first(), 123456789)
                    ->makeValidationRequest()
                    ->approve($fund->organization->identity, $fund->organization);

                $this->setCriteriaAndMakeFundRequest($requester1, $fund, ['children_nth' => 3]);

                $browser->refresh();
                $browser->assertMissing('@fundRequestOptions');
                $browser->waitFor('@takenByPartnerPendingFundRequest');
                $browser->assertPresent('@takenByPartnerPendingFundRequest');
            });
        }, function () use ($fund, $requester1, $requester2) {
            $fund && $this->deleteFund($fund);
            $requester1->records()->delete();
            $requester2->records()->delete();
        });
    }

    /**
     * @throws Throwable
     */
    public function testFundTakenByPartnerVoucher()
    {
        $implementation = Implementation::byKey('nijmegen');

        $fund = $this->createFund($implementation->organization);

        $requester1 = $this->makeIdentity($this->makeUniqueEmail());
        $requester2 = $this->makeIdentity($this->makeUniqueEmail(), 123456789);

        $this->rollbackModels([], function () use ($implementation, $fund, $requester1, $requester2) {
            $this->browse(function (Browser $browser) use ($implementation, $fund, $requester1, $requester2) {
                $browser->visit($implementation->urlWebshop())->waitFor('@headerTitle');
                $this->loginIdentity($browser, $requester1);

                // got to activate page and assert no taken_by_partner block present
                $browser->visit($implementation->urlWebshop("fondsen/$fund->id/activeer"));
                $browser->waitFor('@fundRequestOptions')->assertPresent('@fundRequestOptions');
                $browser->assertMissing('@takenByPartnerVoucher');

                // create pending fund request for requester1
                $fundRequest = $this->setCriteriaAndMakeFundRequest($requester1, $fund, [
                    'children_nth' => 3,
                ]);

                // add partner_bsn record to this fund request
                $fundRequest->records()->create([
                    'record_type_key' => 'partner_bsn',
                    'value' => 123456789,
                ]);

                // approve fund request and assert voucher created
                $employee = $fund->organization->employees()->first();
                $fundRequest->assignEmployee($employee)->approve();
                $this->assertSame(1, $fund->vouchers()->where('identity_id', $requester1->id)->count());

                $this->logout($browser);

                // login requester2
                $browser->visit($implementation->urlWebshop())->waitFor('@headerTitle');
                $this->loginIdentity($browser, $requester2);

                // go to activate page as requester2 and assert taken_by_partner by pending fund request block is visible
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
