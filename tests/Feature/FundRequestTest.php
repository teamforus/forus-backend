<?php

namespace Tests\Feature;

use App\Models\Fund;
use App\Models\FundFormula;
use App\Models\FundRequest;
use App\Models\Identity;
use App\Models\Organization;
use App\Services\MediaService\Traits\UsesMediaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestIdentities;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestRecords;
use Tests\Traits\MakesTestVouchers;

class FundRequestTest extends TestCase
{
    use WithFaker;
    use MakesTestFunds;
    use MakesTestRecords;
    use UsesMediaService;
    use MakesTestVouchers;
    use MakesTestIdentities;
    use DatabaseTransactions;
    use MakesTestOrganizations;
    use MakesTestFundRequests;

    /**
     * @return void
     */
    public function testFundApplyWithOnlyProductFormula()
    {
        $requester = $this->makeIdentity();
        $fund = $this->createFundAndAddProviderWithProducts(1);

        $fund->fund_formulas()->delete();
        $fund->updateFormulaProducts([
            ['product_id' => $fund->fund_providers[0]->organization->products[0]->id],
        ]);

        $this->setCriteriaAndMakeFundRequest($requester, $fund, [
            'children_nth' => 10,
        ]);

        $this->assertEquals(1, $requester->fresh()->vouchers()->count());
    }

    /**
     * @return void
     */
    public function testFundApplyWithOnlyProductFormulaAndMultiplier()
    {
        $requester = $this->makeIdentity();
        $fund = $this->createFundAndAddProviderWithProducts(2);

        $fund->fund_formulas()->delete();
        $fund->updateFormulaProducts([[
            'product_id' => $fund->fund_providers[0]->organization->products[0]->id,
        ], [
            'product_id' => $fund->fund_providers[0]->organization->products[1]->id,
            'record_type_key_multiplier' => 'children_nth',
        ]]);

        $this->setCriteriaAndMakeFundRequest($requester, $fund, [
            'children_nth' => 10,
        ]);

        $this->assertEquals(11, $requester->fresh()->vouchers()->count());
    }

    /**
     * @return void
     */
    public function testFundApplyWithFormulaAndProductFormula()
    {
        $requester = $this->makeIdentity();
        $fund = $this->createFundAndAddProviderWithProducts(2);

        $fund->fund_formulas()->delete();
        $fund->fund_formulas()->create([
            'type' => FundFormula::TYPE_FIXED,
            'amount' => 10,
        ]);

        $fund->updateFormulaProducts([[
            'product_id' => $fund->fund_providers[0]->organization->products[0]->id,
        ], [
            'product_id' => $fund->fund_providers[0]->organization->products[1]->id,
            'record_type_key_multiplier' => 'children_nth',
        ]]);

        $this->setCriteriaAndMakeFundRequest($requester, $fund, [
            'children_nth' => 10,
        ]);

        $this->assertEquals(12, $requester->fresh()->vouchers()->count());
    }

    /**
     * @return void
     */
    public function testFundApplyCustomAmount()
    {
        $requester = $this->makeIdentity();
        $fund = $this->createFundAndAddProviderWithProducts(2);

        $fund->fund_formulas()->delete();
        $fund->fund_formulas()->create([
            'type' => FundFormula::TYPE_FIXED,
            'amount' => 10,
        ]);

        $fund->updateFormulaProducts([[
            'product_id' => $fund->fund_providers[0]->organization->products[0]->id,
        ]]);

        $this->setSimpleFundCriteria($fund, [
            'children_nth' => 10,
        ]);

        $voucher = $this->makeTestVoucher($fund, $requester, amount: 100);

        $this->assertEquals(100, $voucher->amount);
        $this->assertEquals(1, $requester->fresh()->vouchers()->count());
    }

    /**
     * @return void
     */
    public function testFundRequestKeepsOriginalExpirationWhenFundIsExtended(): void
    {
        $this->travelTo('2026-10-01 12:00:00');

        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization, ['end_date' => '2026-10-10']);
        $fundRequest = $this->makeFundRequestForIdentity($fund, $this->makeIdentity($this->makeUniqueEmail()));

        $fund->refresh();
        $fundRequest->refresh();

        $this->assertSame('2026-10-10 00:00:00', $fundRequest->expire_at?->toDateTimeString());
        $this->assertFalse($fundRequest->expired);

        $this->travelTo('2026-10-11 12:00:00');
        $fund->refresh();
        $fundRequest->refresh();

        $this->assertTrue($fundRequest->expired);

        $fund->update(['end_date' => '2026-10-31']);
        $fund->refresh();
        $fundRequest->refresh();

        $this->assertSame('2026-10-31', $fund->end_date->toDateString());
        $this->assertSame('2026-10-10 00:00:00', $fundRequest->expire_at?->toDateTimeString());
        $this->assertTrue($fundRequest->expired);
    }

    /**
     * @return void
     */
    public function testFundRequestCannotBeApprovedBeforeAssignment(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);
        $fundRequest = $this->makeFundRequestForIdentity($fund, $this->makeIdentity($this->makeUniqueEmail()));

        $this->apiFundRequestApproveRequest($fundRequest, $organization->employees[0])->assertForbidden();
        $this->assertEquals($fundRequest::STATE_PENDING, $fundRequest->refresh()->state);
    }

    /**
     * @return void
     */
    public function testFundRequestActionsForbiddenIfExpired(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);
        $employee = $organization->findEmployee($organization->identity);

        $fundRequest = $this->makeFundRequestForIdentity($fund, $this->makeIdentity($this->makeUniqueEmail()));
        $this->apiFundRequestAssignRequest($fundRequest, $employee)->assertSuccessful();

        $questionData = [
            'question' => $this->faker()->text(),
            'text_requirement' => 'required',
            'files_requirement' => 'no',
            'fund_request_record_id' => $fundRequest->records[0]->id,
        ];

        $answerData = ['answer' => $this->faker()->text()];
        // create clarification for future asserts
        $this->apiMakeFundRequestClarificationRequest($fundRequest, $employee, $questionData)->assertSuccessful();

        $fundRequest->update(['expire_at' => now()->subDay()]);

        $this->apiFundRequestApproveRequest($fundRequest, $employee)->assertForbidden();
        $this->apiFundRequestDisregardRequest($fundRequest, ['notify' => false], $employee)->assertForbidden();
        $this->apiFundRequestDeclineRequest($fundRequest, ['notify' => false], $employee)->assertForbidden();

        $this->apiFundRequestResignRequest($fundRequest, $employee)->assertForbidden();

        $clarification = $fundRequest->clarifications[0];
        $this->apiMakeFundRequestClarificationRequest($fundRequest, $employee, $questionData)->assertForbidden();
        $this->apiRespondFundRequestClarificationRequest($clarification, $fundRequest->identity, $answerData)->assertForbidden();
        $this->apiFundRequestClarificationCloseRequest($clarification, $employee, [])->assertForbidden();
        $this->apiFundRequestClarificationUpdateRequest($clarification, $employee, $questionData)->assertForbidden();
    }

    /**
     * @return void
     */
    public function testFundRequestDisregardUndoRespectsExpiration(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);
        $employee = $organization->findEmployee($organization->identity);
        $fundRequest = $this->makeFundRequestForIdentity($fund, $this->makeIdentity($this->makeUniqueEmail()));

        $this->apiFundRequestAssignRequest($fundRequest, $employee)->assertSuccessful();
        $this->apiFundRequestDisregardRequest($fundRequest, ['notify' => false], $employee)->assertSuccessful();

        $fundRequest->update(['expire_at' => today()]);

        $this->apiFundRequestDisregardUndoRequest($fundRequest, $employee)->assertSuccessful();
        $this->assertSame(FundRequest::STATE_PENDING, $fundRequest->refresh()->state);

        $this->apiFundRequestDisregardRequest($fundRequest, ['notify' => false], $employee)->assertSuccessful();
        $fundRequest->update(['expire_at' => now()->subDay()]);

        $this->apiFundRequestDisregardUndoRequest($fundRequest, $employee)
            ->assertForbidden()
            ->assertJsonPath('message', __('policies.fund_requests.expired'));
        $this->assertSame(FundRequest::STATE_DISREGARDED, $fundRequest->refresh()->state);

        $fundRequest->update(['expire_at' => now()->addDay()]);
        $fund->update(['end_date' => now()->subDay()]);

        $this->apiFundRequestDisregardUndoRequest($fundRequest, $employee)
            ->assertForbidden()
            ->assertJsonPath('message', __('policies.fund_requests.expired'));
        $this->assertSame(FundRequest::STATE_DISREGARDED, $fundRequest->refresh()->state);
    }

    /**
     * @param Identity $requester
     * @param Fund $fund
     * @param array $records
     * @return void
     */
    protected function setCriteriaAndMakeFundRequest(Identity $requester, Fund $fund, array $records): void
    {
        $this->setSimpleFundCriteria($fund, $records);

        $recordsList = collect($records)->map(function (string|int $value, string $key) use ($fund) {
            return $this->makeRequestCriterionValue($fund, $key, $value);
        });

        $response = $this->makeFundRequest($requester, $fund, $recordsList, false);
        $response->assertSuccessful();

        $fundRequest = FundRequest::find($response->json('data.id'));
        $employee = $fundRequest->fund->organization->employees[0];

        $this->assertNotNull($fundRequest);
        $this->assertNotNull($employee);

        $fundRequest->assignEmployee($employee);
        $fundRequest->approve();
        $fundRequest->refresh();

        $this->assertTrustedRecords($requester, $fund, $records);
    }

    /**
     * @param Fund $fund
     * @param array $criteria
     * @return Fund
     */
    protected function setSimpleFundCriteria(Fund $fund, array $criteria): Fund
    {
        return $fund->syncCriteria(collect($criteria)->map(fn (string|int|array $value, string $key) => [
            'show_attachment' => false,
            'record_type_key' => $key,
            'operator' => '=',
            'value' => is_array($value) ? null : $value,
            ...is_array($value) ? $value : [],
        ])->toArray());
    }

    /**
     * @param int $productsCount
     * @return Fund
     */
    protected function createFundAndAddProviderWithProducts(int $productsCount): Fund
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);
        $organization->forceFill([
            'fund_request_resolve_policy' => Organization::FUND_REQUEST_POLICY_AUTO_REQUESTED,
        ])->save();

        $fund->fund_config->update([
            'email_required' => false,
            'contact_info_required' => false,
        ]);

        $provider = $this->makeTestFundProvider($this->makeTestOrganization($this->makeIdentity()), $fund);
        $this->makeTestProducts($provider->organization, $productsCount);
        $provider->organization->refresh();

        return $fund->refresh();
    }
}
