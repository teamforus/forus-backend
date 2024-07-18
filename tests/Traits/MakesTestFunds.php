<?php

namespace Tests\Traits;

use App\Models\Fund;
use App\Models\FundCriterion;
use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Prevalidation;
use App\Models\RecordType;
use App\Traits\DoesTesting;
use Illuminate\Testing\TestResponse;
use Throwable;

trait MakesTestFunds
{
    use DoesTesting;
    use MakesProviderAndProducts;

    /**
     * @var string
     */
    protected string $apiUrlCriteria = '/api/v1/platform/organizations/%s/funds/%s/criteria';

    /**
     * @var string
     */
    protected string $apiUrlPrevalidations = '/api/v1/platform/prevalidations';

    /**
     * @param Organization $organization
     * @param array $fundData
     * @param array $fundConfigsData
     * @return Fund
     */
    protected function makeTestFund(
        Organization $organization,
        array $fundData = [],
        array $fundConfigsData = [],
    ): Fund {
        /** @var Fund $fund */
        $fund = $organization->funds()->create([
            'name' => fake()->text(30),
            'start_date' => now()->subDay(),
            'end_date' => now()->addYear(),
            'criteria_editable_after_start' => true,
            'type' => Fund::TYPE_BUDGET,
            ...$fundData,
        ]);

        $fund->changeState($fund::STATE_ACTIVE);

        $implementation = $organization->implementations->isNotEmpty() ?
            $organization->implementations[0] :
            $this->makeTestImplementation($organization);

        $fund->fund_config()->forceCreate([
            'key' => str_slug(token_generator()->generate(4, 4)),
            'implementation_id' => $implementation->id,
            'is_configured' => true,
            'email_required' => true,
            'allow_fund_requests' => true,
            'allow_prevalidations' => true,
            'allow_direct_requests' => true,
            'csv_primary_key' => 'uid',
            ...$fundConfigsData,
        ]);

        try {
            $fund->syncDescriptionMarkdownMedia('cms_media');
        } catch (Throwable) {
            $this->assertTrue(false, 'Could not syncDescriptionMarkdownMedia.');
        }

        if ($fundData['criteria'] ?? false) {
            $fund->syncCriteria($fundData['criteria']);
        }

        if ($fundData['formula_products'] ?? false) {
            $fund->updateFormulaProducts($fundData['formula_products']);
        }

        $fund->criteria()->create([
            'value' => 2,
            'operator' => '>=',
            'show_attachment' => false,
            'record_type_key' => 'children_nth',
        ]);

        $fund->fund_formulas()->create([
            'type' => 'fixed',
            'amount' => 300,
        ]);

        return $fund->refresh();
    }

    /**
     * @param Organization $organization
     * @param array $implementationData
     * @return Implementation
     */
    protected function makeTestImplementation(
        Organization $organization,
        array $implementationData = [],
    ): Implementation {
        return $organization->implementations()->create([
            'name' => fake()->title,
            ...$implementationData,
        ]);
    }

    /**
     * @param Organization $organization
     * @param Fund $fund
     * @param array $rawCriterion
     * @return Prevalidation
     * @throws Throwable
     */
    public function buildFundAndPrevalidation(Organization $organization, Fund $fund, array $rawCriterion = []): Prevalidation
    {
        $this->prepareTestFundWithCriteria($fund);

        // create prevalidation
        $response = $this->makeStorePrevalidationRequest($organization->identity, $fund, [
            $this->makeRequestCriterionValue($fund, "test_bool", 'Ja'),
            $this->makeRequestCriterionValue($fund, "test_iban", fake()->iban),
            $this->makeRequestCriterionValue($fund, "test_date", '01-01-2010'),
            $this->makeRequestCriterionValue($fund, "test_email", fake()->email),
            $this->makeRequestCriterionValue($fund, "test_string", 'lorem_ipsum'),
            $this->makeRequestCriterionValue($fund, "test_string_any", 'ipsum_lorem'),
            $this->makeRequestCriterionValue($fund, "test_number", 7),
            $this->makeRequestCriterionValue($fund, "test_select", 'foo'),
            $this->makeRequestCriterionValue($fund, "test_select_number", 2),
        ], [
            'uid' => token_generator()->generate(32),
            ...$rawCriterion,
        ]);

        $response->assertSuccessful();
        $prevalidation = Prevalidation::find($response->json('data.id'));

        // create products for fund formula products assertion
        // used 'test_number' as record key, created in method 'prepareTestFund'
        $this->makeProviderAndProducts($fund, 'test_number');

        return $prevalidation;
    }

    /**
     * @param Fund $fund
     * @param string $key
     * @param string|null $value
     * @return array
     */
    protected function makeRequestCriterionValue(Fund $fund, string $key, ?string $value): array
    {
        /** @var FundCriterion|null $criterion */
        $criterion = $fund->criteria->firstWhere('record_type_key', $key);

        return [
            'fund_criterion_id' => $criterion?->id,
            'value' => $value,
            'files' => [],
        ];
    }

    /**
     * @param Identity $identity
     * @param Fund $fund
     * @param array $records
     * @param array $extraData
     * @return TestResponse
     */
    protected function makeStorePrevalidationRequest(
        Identity $identity,
        Fund $fund,
        array $records,
        array $extraData = [],
    ): TestResponse {
        $proxy = $this->makeIdentityProxy($identity);
        $criteria = $fund->criteria()->pluck('record_type_key', 'id')->toArray();

        return $this->postJson($this->apiUrlPrevalidations, [
            'fund_id' => $fund->id,
            'data' => [
                ...array_reduce($records, fn ($list, $record) => [
                    ...$list,
                    $criteria[$record['fund_criterion_id']] => $record['value'],
                ], []),
                ...$extraData,
            ],
        ], $this->makeApiHeaders($proxy));
    }

    /**
     * @param Fund $fund
     * @return void
     */
    protected function prepareTestFundWithCriteria(Fund $fund): void
    {
        $fund->criteria()->forceDelete();

        $this->makeRecordType($fund->organization, RecordType::TYPE_BOOL, "test_bool");
        $this->makeRecordType($fund->organization, RecordType::TYPE_IBAN, "test_iban");
        $this->makeRecordType($fund->organization, RecordType::TYPE_DATE, "test_date");
        $this->makeRecordType($fund->organization, RecordType::TYPE_EMAIL, "test_email");
        $this->makeRecordType($fund->organization, RecordType::TYPE_STRING, "test_string");
        $this->makeRecordType($fund->organization, RecordType::TYPE_STRING, "test_string_any");
        $this->makeRecordType($fund->organization, RecordType::TYPE_NUMBER, "test_number");
        $this->makeRecordType($fund->organization, RecordType::TYPE_SELECT, "test_select");
        $this->makeRecordType($fund->organization, RecordType::TYPE_SELECT_NUMBER, "test_select_number");

        $response = $this->updateCriteriaRequest([
            $this->makeCriterion("test_bool", 'Ja', '='),
            $this->makeCriterion("test_iban", null, '*'),
            $this->makeCriterion("test_date", '01-01-2000', '>=', '01-01-1990', '01-01-2020'),
            $this->makeCriterion("test_email", null, '*'),
            $this->makeCriterion("test_string", 'lorem_ipsum', '=', 5, 20),
            $this->makeCriterion("test_string_any", null, '*', 5, 20),
            $this->makeCriterion("test_number", '7', '>=', 5, 10),
            $this->makeCriterion("test_select", 'foo', '='),
            $this->makeCriterion("test_select_number", 2, '>='),
        ], $fund);

        $fund->organization->forceFill([
            'fund_request_resolve_policy' => $fund->organization::FUND_REQUEST_POLICY_AUTO_REQUESTED,
        ])->save();

        $response->assertSuccessful();
    }

    /**
     * @param Organization $organization
     * @param string $type
     * @param string $key
     * @return RecordType
     */
    protected function makeRecordType(
        Organization $organization,
        string $type,
        string $key,
    ): RecordType {
        $recordType = RecordType::create([
            'organization_id' => $organization->id,
            'criteria' => true,
            'type' => $type,
            'key' => $key,
        ]);

        if ($type === $recordType::TYPE_SELECT) {
            $recordType->record_type_options()->createMany([[
                'value' => 'foo',
                'name' => 'Foo',
            ], [
                'value' => 'bar',
                'name' => 'Bar',
            ]]);
        }

        if ($type === $recordType::TYPE_SELECT_NUMBER) {
            $recordType->record_type_options()->createMany([[
                'value' => 1,
                'name' => 'Foo',
            ], [
                'value' => 2,
                'name' => 'Bar',
            ]]);
        }

        return $recordType;
    }

    /**
     * @param string $key
     * @param string|null $value
     * @param string $operator
     * @param string|null $min
     * @param string|null $max
     * @return array
     */
    protected function makeCriterion(
        string $key,
        ?string $value,
        string $operator,
        string $min = null,
        string $max = null,
    ): array {
        return [
            'max' => $max,
            'min' => $min,
            'value' => $value,
            'operator' => $operator,
            'record_type_key' => $key,
            'show_attachment' => false,
        ];
    }

    /**
     * @param array $criteria
     * @param Fund $fund
     * @return TestResponse
     */
    protected function updateCriteriaRequest(array $criteria, Fund $fund): TestResponse
    {
        $url = sprintf($this->apiUrlCriteria, $fund->organization_id, $fund->id);

        return $this->patchJson($url, [
            'criteria' => $criteria,
        ], $this->makeApiHeaders($this->makeIdentityProxy($fund->organization?->identity)));
    }

}