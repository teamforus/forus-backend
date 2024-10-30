<?php

namespace Tests\Unit;

use App\Models\Fund;
use App\Models\Identity;
use App\Services\FileService\Models\File;
use App\Traits\DoesTesting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
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
    public function testFundRequestValidationEmptyRecords(): void
    {
        $fund = $this->prepareFund();

        $identity = $this->makeIdentity();
        $response = $this->makeValidationRequest($identity, $fund);
        $response->assertJsonValidationErrors('records');
    }

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testFundRequestValidation(): void
    {
        // without files, values required
        $fund = $this->prepareFund();

        // values not valid, no files presents
        $identity = $this->makeIdentity();
        $response = $this->makeValidationRequest($identity, $fund, [
            $this->makeRecordValue($identity, $fund, 'children_nth', 2),
            $this->makeRecordValue($identity, $fund, 'net_worth', 900),
            $this->makeRecordValue($identity, $fund, 'gender', 'Female'),
        ]);

        $response->assertJsonValidationErrors('records.0.value');
        $response->assertJsonValidationErrors('records.1.value');
        $response->assertJsonValidationErrors('records.2.value');
        $response->assertJsonMissingValidationErrors('records.0.files');
        $response->assertJsonMissingValidationErrors('records.1.files');
        $response->assertJsonMissingValidationErrors('records.2.files');

        // values valid, files presents
        $identity = $this->makeIdentity();
        $response = $this->makeValidationRequest($identity, $fund, [
            $this->makeRecordValue($identity, $fund, 'children_nth', 3, true),
            $this->makeRecordValue($identity, $fund, 'net_worth', 500, true),
            $this->makeRecordValue($identity, $fund, 'gender', 'Male', true),
        ]);

        $response->assertJsonValidationErrors('records.0.files');
        $response->assertJsonValidationErrors('records.1.files');
        $response->assertJsonValidationErrors('records.2.files');

        // values valid, no files presents
        $identity = $this->makeIdentity();
        $response = $this->makeValidationRequest($identity, $fund, [
            $this->makeRecordValue($identity, $fund, 'children_nth', 3),
            $this->makeRecordValue($identity, $fund, 'net_worth', 500),
            $this->makeRecordValue($identity, $fund, 'gender', 'Male'),
        ]);

        $response->assertSuccessful();
    }

    /**
     * @return void
     */
    public function testFundRequestValidationWithFiles(): void
    {
        // with files, values required
        $fund = $this->prepareFund([
            'show_attachment' => true,
        ]);

        // values not valid, no files presents
        $identity = $this->makeIdentity();
        $response = $this->makeValidationRequest($identity, $fund, [
            $this->makeRecordValue($identity, $fund, 'children_nth', 2),
            $this->makeRecordValue($identity, $fund, 'net_worth', 900),
            $this->makeRecordValue($identity, $fund, 'gender', 'Female'),
        ]);

        $response->assertJsonValidationErrors('records.0.value');
        $response->assertJsonValidationErrors('records.1.value');
        $response->assertJsonValidationErrors('records.2.value');
        $response->assertJsonValidationErrors('records.0.files');
        $response->assertJsonValidationErrors('records.1.files');
        $response->assertJsonValidationErrors('records.2.files');

        // values valid, no files presents
        $identity = $this->makeIdentity();
        $response = $this->makeValidationRequest($identity, $fund, [
            $this->makeRecordValue($identity, $fund, 'children_nth', 3),
            $this->makeRecordValue($identity, $fund, 'net_worth', 500),
            $this->makeRecordValue($identity, $fund, 'gender', 'Male'),
        ]);

        $response->assertJsonValidationErrors('records.0.files');
        $response->assertJsonValidationErrors('records.1.files');
        $response->assertJsonValidationErrors('records.2.files');

        // values valid, files presents
        $identity = $this->makeIdentity();
        $response = $this->makeValidationRequest($identity, $fund, [
            $this->makeRecordValue($identity, $fund, 'children_nth', 3, true),
            $this->makeRecordValue($identity, $fund, 'net_worth', 500, true),
            $this->makeRecordValue($identity, $fund, 'gender', 'Male', true),
        ]);

        $response->assertSuccessful();
    }

    /**
     * @return void
     */
    public function testFundRequestValidationWithFilesOptional(): void
    {
        // with files, values optional
        $fund = $this->prepareFund([
            'optional' => true,
            'show_attachment' => true,
        ]);

        // values not valid, no files presents
        $identity = $this->makeIdentity();
        $response = $this->makeValidationRequest($identity, $fund, [
            $this->makeRecordValue($identity, $fund, 'children_nth', 2),
            $this->makeRecordValue($identity, $fund, 'net_worth', 900),
            $this->makeRecordValue($identity, $fund, 'gender', 'Female'),
        ]);

        $response->assertJsonValidationErrors('records.0.value');
        $response->assertJsonValidationErrors('records.1.value');
        $response->assertJsonValidationErrors('records.2.value');
        $response->assertJsonMissingValidationErrors('records.0.files');
        $response->assertJsonMissingValidationErrors('records.1.files');
        $response->assertJsonMissingValidationErrors('records.2.files');

        // values valid, no files presents
        $identity = $this->makeIdentity();
        $response = $this->makeValidationRequest($identity, $fund, [
            $this->makeRecordValue($identity, $fund, 'children_nth', 3),
            $this->makeRecordValue($identity, $fund, 'net_worth', 500),
            $this->makeRecordValue($identity, $fund, 'gender', 'Male'),
        ]);

        $response->assertSuccessful();

        // values nullable, no files presents
        $identity = $this->makeIdentity();
        $response = $this->makeValidationRequest($identity, $fund, [
            $this->makeRecordValue($identity, $fund, 'children_nth', null),
            $this->makeRecordValue($identity, $fund, 'net_worth', null),
            $this->makeRecordValue($identity, $fund, 'gender', null),
        ]);

        $response->assertSuccessful();

        // values nullable, files presents
        $identity = $this->makeIdentity();
        $response = $this->makeValidationRequest($identity, $fund, [
            $this->makeRecordValue($identity, $fund, 'children_nth', null, true),
            $this->makeRecordValue($identity, $fund, 'net_worth', null, true),
            $this->makeRecordValue($identity, $fund, 'gender', null, true),
        ]);

        $response->assertSuccessful();
    }

    /**
     * @param array $params
     * @return Fund
     */
    public function prepareFund(array $params = []): Fund
    {
        $fund = Fund::first();

        $fund->syncCriteria([[
            'operator' => '>',
            'value' => '2',
            'show_attachment' => false,
            'record_type_key' => 'children_nth',
            ...$params,
        ], [
            'operator' => '<',
            'value' => '501',
            'show_attachment' => false,
            'record_type_key' => 'net_worth',
            ...$params,
        ], [
            'operator' => '=',
            'value' => 'Male',
            'show_attachment' => false,
            'record_type_key' => 'gender',
            ...$params,
        ]]);

        return $fund;
    }

    /**
     * @param Identity $identity
     * @param Fund $fund
     * @param array|null $records
     * @return TestResponse
     */
    protected function makeValidationRequest(
        Identity $identity,
        Fund $fund,
        ?array $records = null
    ): TestResponse {
        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($identity));
        $validationEndpoint = "/api/v1/platform/funds/$fund->id/requests/validate";

        return $this->postJson($validationEndpoint, $records ? [
            'records' => $records,
        ] : [], $apiHeaders);
    }

    /**
     * @param Identity $identity
     * @param Fund $fund
     * @param string $key
     * @param string|int|null $value
     * @param bool $withFiles
     * @return array
     */
    protected function makeRecordValue(
        Identity $identity,
        Fund $fund,
        string $key,
        string|int|null $value,
        bool $withFiles = false,
    ): array {
        return [
            'files' => $withFiles ? array_map(
                fn() => $this->makeFundRequestFile($identity)->uid,
                range(1, 10),
            ) : [],
            'value' => $value,
            'record_type_key' => $key,
            'fund_criterion_id' => $fund->criteria->fresh()->firstWhere('record_type_key', $key)?->id,
        ];
    }

    /**
     * @param Identity $identity
     * @return File
     */
    protected function makeFundRequestFile(Identity $identity): File
    {
        return resolve('file')->uploadSingle(
            UploadedFile::fake()->image(Str::random() . '.jpg', 50, 50),
            'fund_request_record_proof',
        )->update([
            'identity_address' => $identity->address,
        ]);
    }
}
