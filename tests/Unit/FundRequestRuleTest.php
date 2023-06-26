<?php

namespace Tests\Unit;

use App\Models\Fund;
use App\Traits\DoesTesting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Tests\CreatesApplication;
use Tests\TestCase;

class FundRequestRuleTest extends TestCase
{
    use DoesTesting, DatabaseTransactions, CreatesApplication;

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testProductExclusionFromFunds(): void
    {
        $fund = Fund::first();

        $fund->syncCriteria([[
            'operator' => '>',
            'value' => '2',
            'show_attachment' => false,
            'record_type_key' => 'children_nth',
        ], [
            'operator' => '<',
            'value' => '501',
            'show_attachment' => false,
            'record_type_key' => 'net_worth',
        ], [
            'operator' => '=',
            'value' => 'Male',
            'show_attachment' => false,
            'record_type_key' => 'gender',
        ]]);

        $response = $this->makeValidationRequest($fund);
        $response->assertJsonValidationErrors('records');

        $response = $this->makeValidationRequest($fund, [
            $this->makeRecordValue($fund, 'children_nth', 2),
            $this->makeRecordValue($fund, 'net_worth', 900),
            $this->makeRecordValue($fund, 'gender', 'Female'),
        ]);

        $response->assertJsonValidationErrors('records.0.value');
        $response->assertJsonValidationErrors('records.1.value');
        $response->assertJsonValidationErrors('records.2.value');


        $response = $this->makeValidationRequest($fund, [
            $this->makeRecordValue($fund, 'children_nth', 3),
            $this->makeRecordValue($fund, 'net_worth', 500),
            $this->makeRecordValue($fund, 'gender', 'Male'),
        ]);

        $response->assertSuccessful();
    }

    /**
     * @param Fund $fund
     * @param array|null $records
     * @return TestResponse
     */
    protected function makeValidationRequest(Fund $fund, ?array $records = null): TestResponse
    {
        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($this->makeIdentity()));
        $validationEndpoint = "/api/v1/platform/funds/$fund->id/requests/validate";

        return $this->postJson($validationEndpoint, $records ? [
            'records' => $records,
        ] : [], $apiHeaders);
    }

    /**
     * @param Fund $fund
     * @param string $key
     * @param string|int $value
     * @return array
     */
    protected function makeRecordValue(Fund $fund, string $key, string|int $value): array
    {
        return [
            'value' => $value,
            'record_type_key' => $key,
            'fund_criterion_id' => $fund->criteria->fresh()->firstWhere('record_type_key', $key)?->id,
        ];
    }
}
