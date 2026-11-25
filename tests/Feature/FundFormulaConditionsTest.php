<?php

namespace Tests\Feature;

use App\Models\Fund;
use App\Models\FundFormula;
use App\Models\FundFormulaProduct;
use App\Models\FundRequest;
use App\Models\Organization;
use App\Models\RecordType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestProducts;
use Throwable;

class FundFormulaConditionsTest extends TestCase
{
    use DatabaseTransactions;
    use MakesTestFundRequests;
    use MakesTestFunds;
    use MakesTestOrganizations;
    use MakesTestProducts;

    /**
     * @throws Throwable
     * @return void
     */
    public function testAllConditionsOnOneFormulaMustMatch(): void
    {
        ['fund' => $fund, 'date' => $date, 'count' => $count] = $this->makeFormulaFund();
        $formula = $this->makeBudgetFormula($fund, 20);

        $this->addCondition($formula, $date, '<', '2000-01-01');
        $this->addCondition($formula, $count, '>=', '2');
        $fund->refresh();

        $this->assertSame(
            [],
            $fund->matchingBudgetFormulas([$date->key => '1990-01-01', $count->key => '1'])->modelKeys(),
        );

        $this->assertSame(
            [],
            $fund->matchingBudgetFormulas([$date->key => '2001-01-01', $count->key => '2'])->modelKeys(),
        );

        $this->assertSame(
            [$formula->id],
            $fund->matchingBudgetFormulas([$date->key => '1990-01-01', $count->key => '2'])->modelKeys(),
        );
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testAllMatchingBudgetFormulasContributeToAmount(): void
    {
        ['fund' => $fund, 'count' => $count] = $this->makeFormulaFund();

        $this->makeBudgetFormula($fund, 10);

        $this->addCondition($this->makeBudgetFormula($fund, 20), $count, '>=', '2');
        $this->addCondition($this->makeBudgetFormula($fund, 30), $count, '<=', '2');
        $this->addCondition($this->makeBudgetFormula($fund, 40), $count, '>', '2');

        $this->assertSame(60.0, $fund->refresh()->amountForRecordValues([$count->key => '2']));
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testUnmatchedBudgetFormulaDoesNotPreventProductAward(): void
    {
        ['fund' => $fund, 'date' => $date, 'count' => $count] = $this->makeFormulaFund();

        $this->addCondition($this->makeBudgetFormula($fund, 40), $count, '>', '2');
        $productFormula = $this->makeProductFormula($fund);
        $this->addCondition($productFormula, $count, '=', '2');

        $request = $this->approveFormulaRequest($fund, [$date->key => '01-01-1990', $count->key => '2']);
        $voucher = $request->vouchers()->sole();

        $this->assertSame($productFormula->product_id, $voucher->product_id);
        $this->assertEquals(10, $voucher->amount);
        $this->assertFalse($request->vouchers()->whereNull('product_id')->exists());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testMatchingZeroBudgetFormulaCreatesZeroVoucher(): void
    {
        ['fund' => $fund, 'date' => $date, 'count' => $count] = $this->makeFormulaFund();
        $this->addCondition($this->makeBudgetFormula($fund, 0), $count, '=', '2');

        $request = $this->approveFormulaRequest($fund, [$date->key => '01-01-1990', $count->key => '2']);
        $voucher = $request->vouchers()->sole();

        $this->assertNull($voucher->product_id);
        $this->assertEquals(0, $voucher->amount);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testUnmatchedFormulaDoesNotRequireItsMultiplierInput(): void
    {
        ['fund' => $fund, 'date' => $date, 'count' => $count] = $this->makeFormulaFund();

        $missing = $this->makeCriteriaRecordType(
            $fund->organization,
            RecordType::TYPE_NUMBER,
            RecordType::CONTROL_TYPE_NUMBER,
        );

        $formula = $fund->fund_formulas()->create([
            'type' => FundFormula::TYPE_MULTIPLY, 'amount' => 100, 'record_type_key' => $missing->key,
        ]);

        $this->addCondition($formula, $count, '>', '2');
        $this->makeBudgetFormula($fund, 25);

        $request = $this->approveFormulaRequest($fund, [$date->key => '01-01-1990', $count->key => '2']);

        $this->assertEquals(25, $request->vouchers()->sole()->amount);
        $this->assertFalse($request->identity->records()->where('record_type_id', $missing->id)->exists());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testApplicationApprovalUsesConditionalBudgetAndProductFormulas(): void
    {
        ['fund' => $fund, 'date' => $date, 'count' => $count] = $this->makeFormulaFund();
        $adult = $this->makeBudgetFormula($fund, 30);

        $this->addCondition($adult, $date, '<', '2000-01-01');
        $this->addCondition($adult, $count, '>=', '2');
        $this->addCondition($this->makeBudgetFormula($fund, 60), $date, '>=', '2000-01-01');

        $product = $this->makeProductFormula($fund, $count->key);
        $this->addCondition($product, $date, '<', '2000-01-01');
        $excludedProduct = $this->makeProductFormula($fund);
        $this->addCondition($excludedProduct, $date, '>=', '2000-01-01');

        $request = $this->approveFormulaRequest($fund, [$date->key => '15-02-1990', $count->key => '2']);

        // Ordinary budget calculation includes the price of each matching product formula once.
        $this->assertEquals(40, $request->vouchers()->whereNull('product_id')->sole()->amount);

        $this->assertSame(
            ['10.00', '10.00'],
            $request->vouchers()->where('product_id', $product->product_id)->pluck('amount')->all(),
        );

        $this->assertSame(3, $request->vouchers()->count());
        $this->assertFalse($request->vouchers()->where('product_id', $excludedProduct->product_id)->exists());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProductFormulaUpdateUsesItsOwnRowId(): void
    {
        ['fund' => $fund, 'date' => $date, 'count' => $count] = $this->makeFormulaFund();
        $first = $this->makeProductFormula($fund, $count->key);

        $second = $fund->fund_formula_products()->create([
            'product_id' => $first->product_id, 'price' => 10, 'record_type_key_multiplier' => $count->key,
        ]);

        $this->addCondition($first, $date, '<', '2000-01-01');
        $this->addCondition($second, $date, '>=', '2000-01-01');

        $firstConditions = $first->conditions()->get()->toArray();
        $secondBefore = $second->refresh()->toArray();

        $this->apiUpdateFundRequest($fund->organization, $fund, $fund->organization->identity, [
            'formula_products' => [
                ['id' => $first->id, 'product_id' => $first->product_id, 'record_type_key_multiplier' => null],
                ['id' => $second->id, 'product_id' => $second->product_id, 'record_type_key_multiplier' => $count->key],
            ],
        ])->assertOk();

        $this->assertSame([$first->id, $second->id], $fund->fund_formula_products()->orderBy('id')->pluck('id')->all());
        $this->assertNull($first->refresh()->record_type_key_multiplier);
        $this->assertSame($firstConditions, $first->conditions()->get()->toArray());
        $this->assertSame($secondBefore, $second->refresh()->toArray());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProductFormulaOmissionKeepsConditionalRows(): void
    {
        ['fund' => $fund, 'date' => $date] = $this->makeFormulaFund();
        $conditional = $this->makeProductFormula($fund);
        $plain = $this->makeProductFormula($fund);
        $this->addCondition($conditional, $date, '<', '2000-01-01');
        $before = $conditional->refresh()->toArray();

        $this->apiUpdateFundRequest($fund->organization, $fund, $fund->organization->identity, [
            'formula_products' => [],
        ])->assertOk();

        $this->assertSame([$conditional->id], $fund->fund_formula_products()->pluck('id')->all());
        $this->assertSame($before, $conditional->refresh()->toArray());
        $this->assertDatabaseMissing('fund_formula_products', ['id' => $plain->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProductFormulaUpdateRejectsForeignRowId(): void
    {
        ['fund' => $fund] = $this->makeFormulaFund();
        $otherFund = $this->makeTestFund($fund->organization);
        $own = $this->makeProductFormula($fund);
        $foreign = $this->makeProductFormula($otherFund);
        $before = [$own->refresh()->toArray(), $foreign->refresh()->toArray()];

        $this
            ->apiUpdateFundRequest($fund->organization, $fund, $fund->organization->identity, [
                'formula_products' => [['id' => $foreign->id, 'product_id' => $own->product_id]],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('formula_products.0.id');

        $this->assertSame($before, [$own->refresh()->toArray(), $foreign->refresh()->toArray()]);
        $this->assertSame([$own->id], $fund->fund_formula_products()->pluck('id')->all());
        $this->assertSame([$foreign->id], $otherFund->fund_formula_products()->pluck('id')->all());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFormulaResourcesExposeReadableTypedConditions(): void
    {
        ['fund' => $fund, 'date' => $date, 'count' => $count] = $this->makeFormulaFund();
        $date->translateOrNew(app()->getLocale())->fill(['name' => 'Test birth date']);
        $date->save();
        $count->translateOrNew(app()->getLocale())->fill(['name' => 'Test count']);
        $count->save();

        $conditional = $this->makeBudgetFormula($fund, 20);
        $plain = $this->makeBudgetFormula($fund, 30);
        $product = $this->makeProductFormula($fund);

        $this->addCondition($conditional, $date, '<', '01-01-2000');
        $this->addCondition($product, $count, '>=', '2');

        $response = Carbon::executeWithLocale('nl', function () use ($fund) {
            return $this->apiGetOrganizationFundRequest($fund, $fund->organization->identity)->assertOk();
        });

        $formulas = collect($response->json('data.formulas'))->keyBy('id');
        $products = collect($response->json('data.formula_products'))->keyBy('id');

        $this->assertSame('20.00', $formulas[$conditional->id]['amount']);
        $this->assertSame(FundFormula::TYPE_FIXED, $formulas[$conditional->id]['type']);

        $this->assertSame([[
            'record_type_key' => $date->key, 'operator' => '<', 'value' => '2000-01-01',
            'record_type_name' => 'Test birth date', 'value_locale' => '1 jan, 2000',
        ]], $formulas[$conditional->id]['conditions']);

        $this->assertSame([], $formulas[$plain->id]['conditions']);
        $this->assertSame($product->product_id, $products[$product->id]['product_id']);

        $this->assertSame([[
            'record_type_key' => $count->key, 'operator' => '>=', 'value' => '2',
            'record_type_name' => 'Test count', 'value_locale' => '2',
        ]], $products[$product->id]['conditions']);
    }

    /**
     * @throws Throwable
     * @return array{fund: Fund, date: RecordType, count: RecordType}
     */
    private function makeFormulaFund(): array
    {
        $this->travelTo('2026-11-13 12:00:00');
        Config::set('forus.features.dashboard.organizations.funds.formula_products', true);

        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()), [
            'fund_request_resolve_policy' => Organization::FUND_REQUEST_POLICY_AUTO_REQUESTED,
        ]);

        $fund = $this->makeTestFund($organization);
        $fund->criteria()->delete();
        $fund->fund_formulas()->delete();
        $date = $this->makeCriteriaRecordType($organization, RecordType::TYPE_DATE, RecordType::CONTROL_TYPE_DATE);
        $count = $this->makeCriteriaRecordType($organization, RecordType::TYPE_NUMBER, RecordType::CONTROL_TYPE_NUMBER);

        foreach ([$date, $count] as $type) {
            $fund->criteria()->create([
                'record_type_key' => $type->key, 'operator' => '*', 'show_attachment' => false,
            ]);
        }

        return ['fund' => $fund->refresh(), 'date' => $date, 'count' => $count];
    }

    /**
     * @param Fund $fund
     * @param array $values
     * @throws Throwable
     * @return FundRequest
     */
    private function approveFormulaRequest(Fund $fund, array $values): FundRequest
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail(), $this->makeUniqueBsn());
        $request = $this->setCriteriaAndMakeFundRequest($identity, $fund->refresh(), $values);
        $employee = $fund->organization->findEmployee($fund->organization->identity);

        $this->apiFundRequestAssignRequest($request, $employee)->assertSuccessful();
        $this->apiFundRequestApproveRequest($request, $employee)->assertOk();
        $this->assertSame(FundRequest::STATE_APPROVED, $request->refresh()->state);

        return $request;
    }

    /**
     * @param Fund $fund
     * @param int $amount
     * @return FundFormula
     */
    private function makeBudgetFormula(Fund $fund, int $amount): FundFormula
    {
        return $fund->fund_formulas()->create(['type' => FundFormula::TYPE_FIXED, 'amount' => $amount]);
    }

    /**
     * @param Fund $fund
     * @param string|null $multiplier
     * @return FundFormulaProduct
     */
    private function makeProductFormula(Fund $fund, ?string $multiplier = null): FundFormulaProduct
    {
        $provider = $this->makeTestProviderOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $product = $this->makeTestProduct($provider, attributes: ['unlimited_stock' => true]);
        $this->addProductToFund($fund, $product, true);

        return $fund->fund_formula_products()->create([
            'product_id' => $product->id, 'price' => 10, 'record_type_key_multiplier' => $multiplier,
        ]);
    }

    /**
     * @param FundFormula|FundFormulaProduct $formula
     * @param RecordType $type
     * @param string $operator
     * @param string $value
     * @return void
     */
    private function addCondition(
        FundFormula|FundFormulaProduct $formula,
        RecordType $type,
        string $operator,
        string $value,
    ): void {
        $formula->conditions()->create(['record_type_id' => $type->id, 'operator' => $operator, 'value' => $value]);
    }
}
