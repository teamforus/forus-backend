<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\RecordType;
use App\Models\Voucher;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\FundFormulaProductTestTrait;
use Tests\Traits\MakesTestFundProviders;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestIdentities;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class FundCriteriaTest extends TestCase
{
    use WithFaker;
    use MakesTestFunds;
    use MakesTestIdentities;
    use DatabaseTransactions;
    use MakesTestFundRequests;
    use MakesTestOrganizations;
    use MakesTestFundProviders;
    use FundFormulaProductTestTrait;

    /**
     * @var string
     */
    protected string $apiUrlFundsApply = '/api/v1/platform/funds/%s/apply';

    /**
     * @var string
     */
    protected string $apiUrlFundsCheck = '/api/v1/platform/funds/%s/check';

    /**
     * @var string
     */
    protected string $apiUrlFundsRedeem = '/api/v1/platform/funds/redeem';

    protected string $fundKey = 'meedoen';

    protected $dateFormat = 'd-m-Y';

    /**
     * Test basic value type and min/max range.
     * @throws Exception
     * @return void
     */
    public function testCriterionUpdate(): void
    {
        foreach ($this->makeRecordTypes() as $recordType) {
            [$min, $max] = $this->makeCriterionMinMax($recordType);

            $this->updateCriteriaValidationRequest([
                'record_type_key' => $recordType->key,
                'operator' => in_array('=', $recordType->getOperators(), true) ? '=' : '*',
                'value' => $this->makeCriterionValue($recordType),
                'min' => $min,
                'max' => $max,
            ], $this->getFund())->assertSuccessful();

            [$min, $max] = $this->makeCriterionMinMax($recordType, false);

            $this->updateCriteriaValidationRequest([
                'record_type_key' => $recordType->key,
                'operator' => '=',
                'value' => $this->makeCriterionValue($recordType, false),
                'min' => $min,
                'max' => $max,
            ], $this->getFund())->assertJsonValidationErrors([
                'criteria.0.value',
                'criteria.0.min',
                'criteria.0.max',
            ]);
        }
    }

    /**
     * Test value is within min and max.
     * @throws Exception
     * @return void
     */
    public function testCriterionUpdateValueRange(): void
    {
        $recordTypes = $this->makeRecordTypes();

        // test success string within min and max range
        $this->updateCriteriaValidationRequest([
            'record_type_key' => $recordTypes['string']->key,
            'operator' => '=',
            'value' => token_generator()->generate(5),
            'min' => 1,
            'max' => 10,
        ], $this->getFund())->assertSuccessful();

        // test error string outside min and max range
        $this->updateCriteriaValidationRequest([
            'record_type_key' => $recordTypes['string']->key,
            'operator' => '=',
            'value' => token_generator()->generate(100),
            'min' => 1,
            'max' => 10,
        ], $this->getFund())->assertJsonValidationErrors('criteria.0.value');

        // test success number within min and max range
        $this->updateCriteriaValidationRequest([
            'record_type_key' => $recordTypes['number']->key,
            'operator' => '=',
            'value' => 100,
            'min' => 1,
            'max' => 100,
        ], $this->getFund())->assertSuccessful();

        // test error number outside min and max range
        $this->updateCriteriaValidationRequest([
            'record_type_key' => $recordTypes['number']->key,
            'operator' => '=',
            'value' => 1000,
            'min' => 1,
            'max' => 100,
        ], $this->getFund())->assertJsonValidationErrors('criteria.0.value');

        // test success date within min and max range
        $this->updateCriteriaValidationRequest([
            'record_type_key' => $recordTypes['date']->key,
            'operator' => '=',
            'value' => now()->subYears(5)->format($this->dateFormat),
            'min' => now()->subYears(10)->format($this->dateFormat),
            'max' => now()->subYears(5)->format($this->dateFormat),
        ], $this->getFund())->assertSuccessful();

        // test error date outside min and max range
        $this->updateCriteriaValidationRequest([
            'record_type_key' => $recordTypes['date']->key,
            'operator' => '=',
            'value' => now()->subYears(15)->format($this->dateFormat),
            'min' => now()->subYears(10)->format($this->dateFormat),
            'max' => now()->subYears(5)->format($this->dateFormat),
        ], $this->getFund())->assertJsonValidationErrors('criteria.0.value');
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testCriterionCreateAndUpdate(): void
    {
        $fund = $this->getFund();
        $recordTypes = $this->makeRecordTypes();
        $fund->criteria()->delete();

        $criterionData = [
            'record_type_key' => $recordTypes['date']->key,
            'operator' => '=',
            'value' => now()->subYears(10)->format($this->dateFormat),
            'min' => now()->subYears(10)->format($this->dateFormat),
            'max' => now()->subYears(5)->format($this->dateFormat),
        ];

        $criterionData2 = [
            'record_type_key' => $recordTypes['number']->key,
            'operator' => '=',
            'value' => 50,
            'min' => 1,
            'max' => 100,
        ];

        // assert criteria created
        $this->updateCriteriaRequest([$criterionData], $fund)
            ->assertSuccessful()
            ->assertJson(['data' => ['criteria' => [$criterionData]]])
            ->assertJsonCount(1, 'data.criteria');

        // assert criteria appended
        $this->updateCriteriaRequest([$criterionData, $criterionData2], $fund)
            ->assertSuccessful()
            ->assertJson(['data' => ['criteria' => [$criterionData, $criterionData2]]])
            ->assertJsonCount(2, 'data.criteria');

        // assert criteria replaced
        $response = $this->updateCriteriaRequest([$criterionData], $fund)
            ->assertSuccessful()
            ->assertJson(['data' => ['criteria' => [$criterionData]]])
            ->assertJsonCount(1, 'data.criteria');

        // assert existing criteria updated
        $criterionData2['id'] = $response->json('data.criteria')[0]['id'];
        $criterionData2['value'] = 75;

        $this->updateCriteriaRequest([$criterionData2], $fund)
            ->assertSuccessful()
            ->assertJson(['data' => ['criteria' => [$criterionData2]]]);

        $this->assertTrue($fund->fresh()
            ->criteria->where('id', $criterionData2['id'])
            ->where('value', $criterionData2['value'])
            ->isNotEmpty());
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testCriterionUpdateTextsAfterStart(): void
    {
        $fund = $this->getFund();
        $recordTypes = $this->makeRecordTypes();

        $fund->criteria()->delete();

        $criterionData = [
            'record_type_key' => $recordTypes['date']->key,
            'operator' => '=',
            'value' => now()->subYears(10)->format($this->dateFormat),
            'min' => now()->subYears(10)->format($this->dateFormat),
            'max' => now()->subYears(5)->format($this->dateFormat),
        ];

        // assert criteria created
        $this->updateCriteriaRequest([$criterionData], $fund)
            ->assertSuccessful()
            ->assertJson(['data' => ['criteria' => [$criterionData]]])
            ->assertJsonCount(1, 'data.criteria');

        $criterionData = [
            'id' => $fund->criteria[0]->id,
            ...$criterionData,
        ];

        $criterionData2 = [
            'id' => $fund->criteria[0]->id,
            'record_type_key' => $recordTypes['number']->key,
            'operator' => '=',
            'value' => 50,
            'min' => 1,
            'max' => 100,
            'title' => 'Lorem',
            'description' => 'Ipsum',
        ];

        // assert criteria updated
        $this->updateCriteriaRequest([$criterionData2], $fund)
            ->assertSuccessful()
            ->assertJson(['data' => ['criteria' => [$criterionData2]]])
            ->assertJsonCount(1, 'data.criteria');

        // change back to original
        $this->updateCriteriaRequest([$criterionData], $fund)
            ->assertSuccessful()
            ->assertJson(['data' => ['criteria' => [$criterionData]]])
            ->assertJsonCount(1, 'data.criteria');

        $fund->update([
            'criteria_editable_after_start' => false,
        ]);

        // assert only title and description updated
        $this->updateCriteriaRequest([$criterionData2], $fund)
            ->assertSuccessful()
            ->assertJson(['data' => ['criteria' => [[
                ...$criterionData,
                ...Arr::only($criterionData2, ['title', 'description']),
            ]]]])
            ->assertJsonCount(1, 'data.criteria');
    }

    /**
     * @return void
     */
    public function testFundRequestRecordsListFormat(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $fund = $this->makeTestFund($organization);
        $this->addTestCriteriaToFund($fund);

        array_map(fn (TestResponse $response) => $response->assertJsonValidationErrorFor('records'), [
            $this->makeFundRequest($identity, $fund, null, true),
            $this->makeFundRequest($identity, $fund, 5, true),
            $this->makeFundRequest($identity, $fund, '', true),
        ]);

        $response = $this->makeFundRequest($identity, $fund, [
            $this->makeRequestCriterionValue($fund, 'test_bool', 'Ja'),
        ], true);

        $response->assertSuccessful();
    }

    /**
     * @return void
     */
    public function testFundRequestRecordsValidate(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $fund = $this->makeTestFund($organization);
        $this->addTestCriteriaToFund($fund);

        $records = [
            $this->makeRequestCriterionValue($fund, 'test_bool', 'Ja'),
            $this->makeRequestCriterionValue($fund, 'test_iban', fake()->iban),
            $this->makeRequestCriterionValue($fund, 'test_date', '01-01-2010'),
            $this->makeRequestCriterionValue($fund, 'test_email', fake()->email),
            $this->makeRequestCriterionValue($fund, 'test_string', 'lorem_ipsum'),
            $this->makeRequestCriterionValue($fund, 'test_string_any', 'ipsum_lorem'),
            $this->makeRequestCriterionValue($fund, 'test_number', 7),
            $this->makeRequestCriterionValue($fund, 'test_select', 'foo'),
            $this->makeRequestCriterionValue($fund, 'test_select_number', 2),
        ];

        // At least on records has to be submitted
        $this->makeFundRequest($identity, $fund, [], true)->assertJsonValidationErrorFor('records');
        $this->makeFundRequest($identity, $fund, [$records[0]], true)->assertSuccessful();
        $this->makeFundRequest($identity, $fund, $records, true)->assertSuccessful();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundRequestRecordsApplySuccess(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $fund = $this->makeTestFund($organization);
        $this->addTestCriteriaToFund($fund);

        $response = $this->makeFundRequest($identity, $fund, [
            $this->makeRequestCriterionValue($fund, 'test_bool', 'Ja'),
            $this->makeRequestCriterionValue($fund, 'test_iban', fake()->iban),
            $this->makeRequestCriterionValue($fund, 'test_date', '01-01-2010'),
            $this->makeRequestCriterionValue($fund, 'test_email', fake()->email),
            $this->makeRequestCriterionValue($fund, 'test_string', 'lorem_ipsum'),
            $this->makeRequestCriterionValue($fund, 'test_string_any', 'ipsum_lorem'),
            $this->makeRequestCriterionValue($fund, 'test_number', 7),
            $this->makeRequestCriterionValue($fund, 'test_select', 'foo'),
            $this->makeRequestCriterionValue($fund, 'test_select_number', 2),
        ], false);

        $response->assertSuccessful();

        $products = $this->makeProviderAndProducts($fund);
        $this->setFundFormulaProductsForFund($fund, array_random($products['approved'], 3), 'test_number');

        /** @var Employee $employee */
        $fundRequest = FundRequest::find($response->json('data.id'));
        $employee = $fundRequest->fund->organization->employees->first();

        $this->assertNotNull($fundRequest);
        $this->assertNotNull($employee);

        $fundRequest->assignEmployee($employee);
        $fundRequest->approve();
        $fundRequest->refresh();

        $this->assertTrue($fundRequest->isApproved());

        $budgetVoucher = $fundRequest->identity->vouchers->whereNull('product_id')[0] ?? null;
        $totalFormulaAmount = $fund->fund_formulas[0]?->amount;
        $totalFormulaProductsAmount = $fund->fund_formula_products->pluck('price')->sum();

        $this->assertNotNull($budgetVoucher);
        $this->assertEquals($budgetVoucher->amount, $totalFormulaAmount + $totalFormulaProductsAmount);
        $this->assertFundFormulaProductVouchersCreatedByMainVoucher($budgetVoucher);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testStoreSinglePrevalidationByRedeem()
    {
        $this->assertPrevalidationSuccess();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testStoreSinglePrevalidationByApply()
    {
        $this->assertPrevalidationSuccess(false);
    }

    /**
     * @param bool $assertRedeem
     * @throws Throwable
     * @return void
     */
    public function assertPrevalidationSuccess(bool $assertRedeem = true): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $identity = $this->makeIdentity(email: $this->makeUniqueEmail(), bsn: 123456789);
        $fund = $this->makeTestFund($organization);

        $identityHeaders = [
            ...$this->makeApiHeaders($identity),
            'Client-Type' => 'webshop',
            'Client-Key' => $fund->fund_config->implementation->key,
        ];

        $this->addTestCriteriaToFund($fund);
        $prevalidation = $this->makePrevalidationForTestCriteria($organization, $fund);
        $products = $this->makeProviderAndProducts($fund);

        $this->setFundFormulaProductsForFund($fund, array_random($products['approved'], 3), 'test_number');

        if ($assertRedeem) {
            $code = $prevalidation->uid;
            $response = $this->postJson($this->apiUrlFundsRedeem, compact('code'), $identityHeaders);
            $response->assertSuccessful();
            $voucher = $identity->vouchers()->whereNull('product_id')->first();
        } else {
            $prevalidation->assignToIdentity($identity);
            $response = $this->postJson(sprintf($this->apiUrlFundsApply, $fund->id), [], $identityHeaders);
            $response->assertSuccessful();
            $voucher = Voucher::find($response->json('data.id'));
        }

        $this->assertNotNull($voucher);
        $this->assertFundFormulaProductVouchersCreatedByMainVoucher($voucher);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testPrevalidationCheckSuccess(): void
    {
        $bsn = '123456789';
        $organization = $this->makeTestOrganization($this->makeIdentity(), ['bsn_enabled' => true]);
        $identity = $this->makeIdentity(email: $this->makeUniqueEmail(), bsn: $bsn);
        $fund = $this->makeTestFund($organization, [], ['csv_primary_key' => 'bsn']);

        $identityHeaders = [
            ...$this->makeApiHeaders($identity),
            'Client-Type' => 'webshop',
            'Client-Key' => $fund->fund_config->implementation->key,
        ];

        $this->addTestCriteriaToFund($fund);
        $this->makePrevalidationForTestCriteria($organization, $fund, $bsn);

        $products = $this->makeProviderAndProducts($fund);

        $this->setFundFormulaProductsForFund($fund, array_random($products['approved'], 3), 'test_number');

        $response = $this->postJson(sprintf($this->apiUrlFundsCheck, $fund->id), [], $identityHeaders);
        $response->assertSuccessful();

        /** @var Voucher $voucher */
        $voucher = $identity->vouchers()->whereNull('product_id')->first();

        $this->assertNotNull($voucher);
        $this->assertFundFormulaProductVouchersCreatedByMainVoucher($voucher);
    }

    /**
     * @return void
     */
    public function testFundRequestRecordsApplyErrorValue(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $fund = $this->makeTestFund($organization);
        $this->addTestCriteriaToFund($fund);

        $records = [
            $this->makeRequestCriterionValue($fund, 'test_bool', 'Nee'),
            $this->makeRequestCriterionValue($fund, 'test_iban', fake()->name),
            $this->makeRequestCriterionValue($fund, 'test_date', '2010-01-01'),
            $this->makeRequestCriterionValue($fund, 'test_email', fake()->name),
            $this->makeRequestCriterionValue($fund, 'test_string', 'ipsum_lorem'),
            $this->makeRequestCriterionValue($fund, 'test_number', 5),
            $this->makeRequestCriterionValue($fund, 'test_select', 'bar'),
            $this->makeRequestCriterionValue($fund, 'test_select_number', 1),
        ];

        $errors = array_map(fn ($index) => "records.$index.value", range(0, count($records) - 1));
        $response = $this->makeFundRequest($identity, $fund, $records, false);

        $response->assertJsonValidationErrors($errors);
    }

    /**
     * @return void
     */
    public function testFundRequestRecordsApplyErrorValueMin(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $fund = $this->makeTestFund($organization);
        $this->addTestCriteriaToFund($fund);

        $records = [
            $this->makeRequestCriterionValue($fund, 'test_date', '01-01-1995'),
            $this->makeRequestCriterionValue($fund, 'test_string', 'test'),
            $this->makeRequestCriterionValue($fund, 'test_string_any', 'test'),
            $this->makeRequestCriterionValue($fund, 'test_number', 4),
        ];

        $errors = array_map(fn ($index) => "records.$index.value", range(0, count($records) - 1));
        $response = $this->makeFundRequest($identity, $fund, $records, false);

        $response->assertJsonValidationErrors($errors);
    }

    /**
     * @return void
     */
    public function testFundRequestRecordsApplyErrorValueMax(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $fund = $this->makeTestFund($organization);
        $this->addTestCriteriaToFund($fund);

        $records = [
            $this->makeRequestCriterionValue($fund, 'test_date', '01-01-2030'),
            $this->makeRequestCriterionValue($fund, 'test_string', fake()->text(100)),
            $this->makeRequestCriterionValue($fund, 'test_string_any', fake()->text(100)),
            $this->makeRequestCriterionValue($fund, 'test_number', 15),
        ];

        $errors = array_map(fn ($index) => "records.$index.value", range(0, count($records) - 1));
        $response = $this->makeFundRequest($identity, $fund, $records, false);

        $response->assertJsonValidationErrors($errors);
    }

    /**
     * @return void
     */
    public function testFundRequestRecordsApplyErrorValueOptional(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $fund = $this->makeTestFund($organization);
        $this->addTestCriteriaToFund($fund);
        $fund->criteria()->update([
            'optional' => true,
        ]);

        $records = [
            $this->makeRequestCriterionValue($fund, 'test_bool', null),
            $this->makeRequestCriterionValue($fund, 'test_iban', null),
            $this->makeRequestCriterionValue($fund, 'test_date', null),
            $this->makeRequestCriterionValue($fund, 'test_email', null),
            $this->makeRequestCriterionValue($fund, 'test_string', null),
            $this->makeRequestCriterionValue($fund, 'test_string_any', null),
            $this->makeRequestCriterionValue($fund, 'test_number', null),
            $this->makeRequestCriterionValue($fund, 'test_select', null),
            $this->makeRequestCriterionValue($fund, 'test_select_number', null),
        ];

        $response = $this->makeFundRequest($identity, $fund, $records, false);
        $response->assertSuccessful();
    }

    /**
     * @return void
     */
    public function testFundRequestRecordAttachments(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $fund = $this->makeTestFund($organization);

        $fund->criteria->each(fn ($criterion) => $criterion->fund_criterion_rules()->delete());
        $fund->criteria()->forceDelete();
        $this->makeRecordType($fund->organization, RecordType::TYPE_DATE, 'test_date');

        $this->updateCriteriaRequest([[
            ...$this->makeCriterion('test_date', '01-01-2000', '>=', '01-01-1990', '01-01-2020'),
            'show_attachment' => true,
        ]], $fund)->assertSuccessful();

        $fund->refresh();

        $file = UploadedFile::fake()->image('test.jpg');
        $fileModel = resolve('file')->uploadSingle($file, 'fund_request_record_proof');
        $fileModel->update([
            'identity_address' => $identity->address,
        ]);

        $records = [[
            ...$this->makeRequestCriterionValue($fund, 'test_date', '01-01-2015'),
            'files' => [$fileModel->uid],
        ]];

        $response = $this->makeFundRequest($identity, $fund, $records, false);
        $response->assertSuccessful();
    }

    /**
     * @param RecordType $recordType
     * @param bool $valid
     * @throws Exception
     * @return string|null
     */
    protected function makeCriterionValue(RecordType $recordType, bool $valid = true): ?string
    {
        $options = $recordType->getOptions();

        if ($valid) {
            return match ($recordType->type) {
                $recordType::TYPE_NUMBER => random_int(1, 10),
                $recordType::TYPE_DATE => now()->subYears(15)->format($this->dateFormat),
                $recordType::TYPE_BOOL => random_int(0, 1) ? 'Ja' : 'Nee',
                $recordType::TYPE_IBAN => $this->faker->iban('NL'),
                $recordType::TYPE_EMAIL => $this->faker->email(),
                $recordType::TYPE_SELECT,
                $recordType::TYPE_SELECT_NUMBER => !empty($options) ? array_first($options) : null,
                $recordType::TYPE_STRING => token_generator()->generate(10),
                default => token_generator()->generate(5),
            };
        }

        return match ($recordType->type) {
            $recordType::TYPE_STRING => token_generator()->generate(100),
            $recordType::TYPE_NUMBER => random_int(100, 200),
            default => 'invalid',
        };
    }

    /**
     * @param RecordType $recordType
     * @param bool $valid
     * @return array|null
     */
    protected function makeCriterionMinMax(RecordType $recordType, bool $valid = true): ?array
    {
        return $valid ? match ($recordType->type) {
            $recordType::TYPE_DATE => [
                now()->subYears(15)->format($this->dateFormat),
                now()->subYears(5)->format($this->dateFormat),
            ],
            default => [1, 10],
        } : ['invalid',  'invalid'];
    }

    /**
     * @throws Exception
     * @return RecordType[] array
     */
    protected function makeRecordTypes(): array
    {
        return Arr::keyBy(array_map(fn (string $type) => RecordType::create([
            'type' => $type,
            'name' => $type . random_int(10000, 99999),
            'key' => $type . random_int(10000, 99999),
            'organization_id' => $this->getFund()->organization_id,
            'criteria' => true,
        ]), RecordType::TYPES), 'type');
    }

    /**
     * @return Fund|null
     */
    protected function getFund(): ?Fund
    {
        $fund = Fund::whereRelation('fund_config', 'key', $this->fundKey)->first();

        $this->assertNotNull($fund);

        return $fund;
    }

    /**
     * @param array $criteria
     * @param Fund $fund
     * @return TestResponse
     */
    protected function updateCriteriaValidationRequest(array $criteria, Fund $fund): TestResponse
    {
        $url = sprintf($this->apiUrlCriteria, $fund->organization_id, $fund->id) . '/validate';

        return $this->patchJson($url, [
            'criteria' => [$criteria],
        ], $this->makeApiHeaders($fund->organization?->identity));
    }
}
