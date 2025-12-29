<?php

namespace Tests\Feature;

use App\Models\Prevalidation;
use App\Models\PrevalidationRecord;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Arr;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class PrevalidationsTest extends TestCase
{
    use WithFaker;
    use MakesTestFunds;
    use DatabaseTransactions;
    use MakesTestOrganizations;

    /**
     * Test creating a prevalidation for test criteria.
     * @throws Throwable
     * @return void
     */
    public function testPrevalidationCreate(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization, fundConfigsData: ['csv_primary_key' => 'uid']);
        $this->addTestCriteriaToFund($fund);

        $this->makePrevalidationForTestCriteria($organization, $fund);
    }

    /**
     * Assert that if a prevalidation already exists (same primary key), you cannot create another one.
     * @throws Throwable
     * @return void
     */
    public function testPrevalidationCreateWithSameKeyValidationError(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization, fundConfigsData: ['csv_primary_key' => 'uid']);
        $uid = token_generator()->generate(32);

        $this->addTestCriteriaToFund($fund);

        // first prevalidation
        $this->makePrevalidationForTestCriteria($organization, $fund, $uid);

        // second prevalidation with same primary_key is expected to fail
        $this
            ->apiMakePrevalidationForTestCriteriaRequest($organization, $fund, $uid)
            ->assertJsonValidationErrors('data.uid');
    }

    /**
     * Test batch upload when CSV has 2 records: the first record already exists with identical records and primary key
     * in the database. The system skips the first one (does nothing) and creates the second one.
     * @throws Throwable
     * @return void
     */
    public function testPrevalidationBatchUploadCase1(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization, fundConfigsData: ['csv_primary_key' => 'uid']);
        $this->addTestCriteriaToFund($fund);

        // create one prevalidation for future test prevalidation creation with the same primary key and records
        $uid = token_generator()->generate(32);
        $existingPrevalidation = $this->makePrevalidationForTestCriteria($organization, $fund, $uid);

        // prepare prevalidation records for upload
        $existingPrevalidationData = $existingPrevalidation->prevalidation_records
            ->mapWithKeys(fn (PrevalidationRecord $record) => [$record->record_type->key => $record->value])
            ->toArray();

        // prepare new prevalidation with unique primary_key
        $newPrevalidationData = [
            'uid' => token_generator()->generate(32),
            'test_bool' => 'Ja',
            'test_iban' => fake()->iban,
            'test_date' => '01-01-2010',
            'test_email' => fake()->email,
            'test_string' => 'lorem_ipsum',
            'test_string_any' => 'lorem_ipsum',
            'test_number' => 7,
            'test_select' => 'foo',
            'test_select_number' => 2,
        ];

        $response = $this->makeStorePrevalidationBatchRequest($organization, $fund, [$existingPrevalidationData, $newPrevalidationData]);

        $response->assertSuccessful();

        // assert first prevalidation didn't change
        $this->assertRecordsEquals($existingPrevalidation->refresh(), $existingPrevalidationData);

        $this->assertCount(1, $response->json('data'));
        $newPrevalidation = Prevalidation::find($response->json('data')[0]['id']);
        $this->assertNotNull($newPrevalidation);

        // assert second prevalidation created
        $this->assertRecordsEquals($newPrevalidation, $newPrevalidationData);
    }

    /**
     * Test batch upload when CSV has 2 records: the first record already exists with the same primary key but has
     * different record values. The system should ask for confirmation before updating the first record and after
     * confirmation update the first one and create the second one.
     * @throws Throwable
     * @return void
     */
    public function testPrevalidationBatchUploadCase2(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization, fundConfigsData: ['csv_primary_key' => 'uid']);
        $this->addTestCriteriaToFund($fund);

        // create one prevalidation for future test prevalidation creation with the same primary key and records
        $uid = token_generator()->generate(32);
        $existingPrevalidation = $this->makePrevalidationForTestCriteria($organization, $fund, $uid);

        // prepare prevalidation records for upload
        $existingPrevalidationData = $existingPrevalidation->prevalidation_records
            ->mapWithKeys(fn (PrevalidationRecord $record) => [$record->record_type->key => $record->value])
            ->toArray();

        // change some record values to test that records must be updated
        $existingPrevalidationData['test_number'] = 8;
        $existingPrevalidationData['test_string_any'] = 'new_lorem_ipsum';

        // prepare new prevalidation with unique primary_key
        $newPrevalidationData = [
            'uid' => token_generator()->generate(32),
            'test_bool' => 'Ja',
            'test_iban' => fake()->iban,
            'test_date' => '01-01-2010',
            'test_email' => fake()->email,
            'test_string' => 'lorem_ipsum',
            'test_string_any' => 'lorem_ipsum',
            'test_number' => 7,
            'test_select' => 'foo',
            'test_select_number' => 2,
        ];

        $response = $this->makeStorePrevalidationBatchRequest($organization, $fund, [
            $existingPrevalidationData, $newPrevalidationData,
        ], [$uid]);

        $response->assertSuccessful();

        // assert first prevalidation updated (as we changed $existingPrevalidationData)
        $this->assertRecordsEquals($existingPrevalidation->refresh(), $existingPrevalidationData);
        $this->assertCount(2, $response->json('data'));

        $newPrevalidationId = Arr::first(
            $response->json('data'),
            fn (array $prevalidation) => $prevalidation['id'] !== $existingPrevalidation->id
        )['id'];

        $newPrevalidation = Prevalidation::find($newPrevalidationId);
        $this->assertNotNull($newPrevalidation);

        // assert second prevalidation created
        $this->assertRecordsEquals($newPrevalidation, $newPrevalidationData);
    }

    /**
     * Test batch upload when CSV has 2 records: both already exist and haven't changed.
     * The system shows a success message and skips both records.
     * @throws Throwable
     * @return void
     */
    public function testPrevalidationBatchUploadCase3(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization, fundConfigsData: ['csv_primary_key' => 'uid']);
        $this->addTestCriteriaToFund($fund);

        // create one prevalidation for future test prevalidation creation with the same primary key and records
        $firstPrevalidation = $this->makePrevalidationForTestCriteria($organization, $fund);
        $secondPrevalidation = $this->makePrevalidationForTestCriteria($organization, $fund);

        // prepare prevalidation records for upload
        $firstPrevalidationData = $firstPrevalidation->prevalidation_records
            ->mapWithKeys(fn (PrevalidationRecord $record) => [$record->record_type->key => $record->value])
            ->toArray();

        $secondPrevalidationData = $secondPrevalidation->prevalidation_records
            ->mapWithKeys(fn (PrevalidationRecord $record) => [$record->record_type->key => $record->value])
            ->toArray();

        $response = $this->makeStorePrevalidationBatchRequest($organization, $fund, [
            $firstPrevalidationData, $secondPrevalidationData,
        ]);

        $response->assertSuccessful();
        $this->assertCount(0, $response->json('data'));

        // assert first prevalidation didn't change
        $this->assertRecordsEquals($firstPrevalidation->refresh(), $firstPrevalidationData);

        // assert second prevalidation didn't change
        $this->assertRecordsEquals($secondPrevalidation->refresh(), $secondPrevalidationData);
    }

    /**
     * @param Prevalidation $prevalidation
     * @param array $data
     * @return void
     */
    private function assertRecordsEquals(Prevalidation $prevalidation, array $data): void
    {
        $records = $prevalidation->prevalidation_records;

        foreach ($data as $field => $value) {
            $record = $records->first(fn (PrevalidationRecord $record) => $record->record_type->key === $field);
            $this->assertNotNull($record);
            $this->assertEquals($value, $record->value);
        }
    }
}
