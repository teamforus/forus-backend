<?php

namespace Feature;

use App\Models\Fund;
use App\Models\RecordType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Arr;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class FundCriteriaTest extends TestCase
{
    use WithFaker, DatabaseTransactions;

    /**
     * @var string
     */
    protected string $apiUrl = '/api/v1/platform/organizations/%s/funds/%s/criteria';

    protected string $fundKey = 'meedoen';

    protected $dateFormat = 'd-m-Y';

    /**
     * Test basic value type and min/max range
     * @return void
     * @throws \Exception
     */
    public function testCriterionUpdate(): void
    {
        foreach ($this->makeRecordTypes() as $recordType) {
            [$min, $max] = $this->makeCriterionMinMax($recordType);

            $this->updateCriteriaValidationRequest([
                'record_type_key' => $recordType->key,
                'operator' => in_array('=', $recordType->getOperators()) ? '=' : '*',
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
     * Test value is within min and max
     * @return void
     * @throws \Exception
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
     * @return void
     * @throws \Exception
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
        $this->updateCriterionRequest([$criterionData], $fund)
            ->assertSuccessful()
            ->assertJson(['data' => ['criteria' => [$criterionData]]])
            ->assertJsonCount(1, 'data.criteria');

        // assert criteria appended
        $this->updateCriterionRequest([$criterionData, $criterionData2], $fund)
            ->assertSuccessful()
            ->assertJson(['data' => ['criteria' => [$criterionData, $criterionData2]]])
            ->assertJsonCount(2, 'data.criteria');

        // assert criteria replaced
        $response = $this->updateCriterionRequest([$criterionData], $fund)
            ->assertSuccessful()
            ->assertJson(['data' => ['criteria' => [$criterionData]]])
            ->assertJsonCount(1, 'data.criteria');

        // assert existing criteria updated
        $criterionData2['id'] = $response->json('data.criteria')[0]['id'];
        $criterionData2['value'] = 75;

        $this->updateCriterionRequest([$criterionData2], $fund)
            ->assertSuccessful()
            ->assertJson(['data' => ['criteria' => [$criterionData2]]]);

        $this->assertTrue($fund->fresh()
            ->criteria->where('id', $criterionData2['id'])
            ->where('value', $criterionData2['value'])
            ->isNotEmpty());
    }

    /**
     * @param RecordType $recordType
     * @param bool $valid
     * @return string|null
     * @throws \Exception
     */
    protected function makeCriterionValue(RecordType $recordType, bool $valid = true): ?string
    {
        $options = $recordType->getOptions();

        if ($valid) {
            return match ($recordType->type) {
                "number" => random_int(1, 10),
                "date" => now()->subYears(15)->format($this->dateFormat),
                "bool" => random_int(0, 1) ? 'true' : 'false',
                "iban" => $this->faker->iban('NL'),
                "email" => $this->faker->email(),
                'select' => !empty($options) ? array_first($options) : null,
                default => token_generator()->generate(10),
            };
        }

        return match ($recordType->type) {
            "string" => token_generator()->generate(100),
            "number" => random_int(100, 200),
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
            "date" => [
                now()->subYears(15)->format($this->dateFormat),
                now()->subYears(5)->format($this->dateFormat),
            ],
            default => [1, 10],
        } : ['invalid',  'invalid'];
    }

    /**
     * @return RecordType[] array
     * @throws \Exception
     */
    protected function makeRecordTypes(): array
    {
        return Arr::keyBy(array_map(fn(string $type) => RecordType::create([
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
    protected function updateCriterionRequest(array $criteria, Fund $fund): TestResponse
    {
        $url = sprintf($this->apiUrl, $fund->organization_id, $fund->id);

        return $this->patchJson($url, [
            'criteria' => $criteria,
        ], $this->makeApiHeaders($this->makeIdentityProxy($fund->organization?->identity)));
    }

    /**
     * @param array $criteria
     * @param Fund $fund
     * @return TestResponse
     */
    protected function updateCriteriaValidationRequest(array $criteria, Fund $fund): TestResponse
    {
        $url = sprintf($this->apiUrl, $fund->organization_id, $fund->id) . '/validate';

        return $this->patchJson($url, [
            'criteria' => [$criteria],
        ], $this->makeApiHeaders($this->makeIdentityProxy($fund->organization?->identity)));
    }
}
