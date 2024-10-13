<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Fund;
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

class FundRequestTest extends TestCase
{
    use UsesMediaService;
    use DatabaseTransactions;
    use WithFaker;
    use MakesTestFunds;
    use MakesTestOrganizations;
    use MakesTestIdentities;
    use MakesTestRecords;
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
            'type' => 'fixed',
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
            'type' => 'fixed',
            'amount' => 10,
        ]);

        $fund->updateFormulaProducts([[
            'product_id' => $fund->fund_providers[0]->organization->products[0]->id,
        ]]);

        $this->setSimpleFundCriteria($fund, [
            'children_nth' => 10,
        ]);

        $voucher = $fund->makeVoucher($requester, amount: 100);

        $this->assertEquals(100, $voucher->amount);
        $this->assertEquals(1, $requester->fresh()->vouchers()->count());
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

        /** @var Employee $employee */
        $fundRequest = FundRequest::find($response->json('data.id'));
        $employee = $fundRequest->fund->organization->employees->first();

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
        return $fund->syncCriteria(collect($criteria)->map(fn (string|int $value, string $key) => [
            'show_attachment' => false,
            'record_type_key' => $key,
            'operator' => '=',
            'value' => $value,
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