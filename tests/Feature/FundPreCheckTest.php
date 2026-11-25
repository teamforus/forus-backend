<?php

namespace Tests\Feature;

use App\Models\Fund;
use App\Models\FundFormula;
use App\Models\Implementation;
use App\Models\RecordType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestProducts;
use Throwable;

class FundPreCheckTest extends TestCase
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
    public function testPreCheckReturnsUnconditionalAmountsAndProductCounts(): void
    {
        ['fund' => $fund, 'implementation' => $implementation, 'count' => $count] = $this->makePreCheckFund();
        $fund->fund_formulas()->create(['type' => FundFormula::TYPE_FIXED, 'amount' => 40]);

        $provider = $this->makeTestProviderOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $product = $this->makeTestProduct($provider);

        $this->addProductToFund($fund, $product, true);
        $fund->fund_formula_products()->create(['product_id' => $product->id, 'price' => 10]);

        $response = $this->apiCalculatePreCheckRequest($implementation, [
            'organization_id' => $fund->organization_id,
            'records' => [['key' => $count->key, 'value' => '2']],
        ])->assertOk();

        $response->assertJsonCount(1, 'funds')
            ->assertJsonPath('funds.0.id', $fund->id)
            ->assertJsonPath('funds.0.is_valid', true)
            ->assertJsonPath('funds.0.amount_for_identity', '50.00')
            ->assertJsonPath('funds.0.amount_total', '50.00')
            ->assertJsonPath('funds.0.product_count', 1)
            ->assertJsonPath('products_count_total', 1)
            ->assertJsonPath('funds.0.fund_formula_products.products.0.count', 1);

        $this->assertEquals(50, $response->json('amount_total'));
        $this->assertEquals(50, $response->json('amount_total_valid'));
        $this->assertEquals(10, $response->json('funds.0.products_amount_total'));
        $this->assertFalse($fund->vouchers()->exists());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testPreCheckReportsMultiplyVoucherCountAndTotal(): void
    {
        ['fund' => $fund, 'implementation' => $implementation, 'count' => $count] = $this->makePreCheckFund();

        $fund->fund_formulas()->create([
            'type' => FundFormula::TYPE_MULTIPLY_VOUCHERS, 'amount' => 25, 'record_type_key' => $count->key,
        ]);

        $response = $this->apiCalculatePreCheckRequest($implementation, [
            'organization_id' => $fund->organization_id,
            'records' => [['key' => $count->key, 'value' => '2']],
        ])->assertOk();

        $response->assertJsonCount(1, 'funds')
            ->assertJsonPath('funds.0.id', $fund->id)
            ->assertJsonPath('funds.0.is_valid', true)
            ->assertJsonCount(1, 'funds.0.fund_formula_products.items')
            ->assertJsonPath('funds.0.fund_formula_products.items.0.count', 2)
            ->assertJsonPath('funds.0.fund_formula_products.items.0.amount', '50.00')
            ->assertJsonPath('funds.0.fund_formula_products.items.0.total', '€ 50,-')
            ->assertJsonPath('funds.0.fund_formula_products.total_amount', '€ 50,-');

        $this->assertFalse($fund->vouchers()->exists());
    }

    /**
     * @throws Throwable
     * @return array{fund: Fund, implementation: Implementation, count: RecordType}
     */
    private function makePreCheckFund(): array
    {
        $this->travelTo('2026-11-13 12:00:00');
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()), [
            'allow_pre_checks' => true,
        ]);

        $implementation = $this->makeTestImplementation($organization, ['pre_check_enabled' => true]);
        $fund = $this->makeTestFund($organization, implementation: $implementation);
        $fund->criteria()->delete();
        $fund->fund_formulas()->delete();
        $count = $this->makeCriteriaRecordType($organization, RecordType::TYPE_NUMBER, RecordType::CONTROL_TYPE_NUMBER);
        $count->update(['pre_check' => true]);

        $fund->criteria()->create([
            'record_type_key' => $count->key, 'operator' => '>=', 'value' => '1', 'show_attachment' => false,
        ]);

        $preCheckRecord = $implementation->pre_checks_records()->create([
            'record_type_key' => $count->key, 'title' => 'Test count', 'title_short' => 'Count', 'order' => 1,
        ]);

        $preCheckRecord->settings()->create(['fund_id' => $fund->id, 'is_knock_out' => true]);

        return compact('fund', 'implementation', 'count');
    }
}
