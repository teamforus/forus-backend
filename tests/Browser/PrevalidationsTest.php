<?php

namespace Browser;

use App\Models\Fund;
use App\Models\FundCriterion;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Prevalidation;
use App\Models\PrevalidationRecord;
use App\Models\RecordType;
use Facebook\WebDriver\Exception\ElementClickInterceptedException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\NavigatesFrontendDashboard;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFunds;
use Throwable;

class PrevalidationsTest extends DuskTestCase
{
    use MakesTestFunds;
    use HasFrontendActions;
    use RollbackModelsTrait;
    use NavigatesFrontendDashboard;

    protected string $csvPath = 'public/prevalidation_batch_test.csv';

    /**
     * @throws Throwable
     * @return void
     */
    public function testPrevalidationCreate(): void
    {
        $implementation = Implementation::byKey('nijmegen');

        $fund = $this->makeTestFund($implementation->organization, fundConfigsData: ['csv_primary_key' => 'uid']);
        $this->addTestCriteriaToFund($fund);

        $data = [[
            'record_type_key' => 'uid',
            'control_type' => 'text',
            'assert_valid' => '12345678',
        ], [
            'record_type_key' => 'test_iban',
            'control_type' => 'text',
            'assert_valid' => fake()->iban,
            'assert_invalid' => '123456789',
        ], [
            'record_type_key' => 'test_date',
            'control_type' => 'date',
            'assert_valid' => '01-01-2010',
            'assert_invalid' => '01-01-1980',
        ], [
            'record_type_key' => 'test_email',
            'control_type' => 'text',
            'assert_valid' => fake()->email,
            'assert_invalid' => 'fake_email',
        ], [
            'record_type_key' => 'test_string_any',
            'control_type' => 'text',
            'assert_valid' => 'lorem_ipsum',
        ], [
            'record_type_key' => 'test_number',
            'control_type' => 'number',
            'assert_valid' => 7,
            'assert_invalid' => 5,
        ], [
            'record_type_key' => 'test_select',
            'control_type' => 'select',
            'assert_valid' => 'Foo',
            'assert_invalid' => 'Bar',
        ]];

        $this->rollbackModels([], function () use ($implementation, $fund, $data) {
            $this->browse(function (Browser $browser) use ($implementation, $fund, $data) {
                $browser->visit($implementation->urlSponsorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $implementation->organization->identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $implementation->organization->identity);
                $this->selectDashboardOrganization($browser, $implementation->organization);

                $this->goToPrevalidationsPage($browser, $fund);
                $this->openPrevalidationCreateModal($browser);
                $this->fillFormAndSubmit($browser, $fund, $data);

                // assert prevalidation created
                $prevalidation = Prevalidation::where('fund_id', $fund->id)->first();
                $this->assertNotNull($prevalidation);

                // search new prevalidation in table
                $browser->refresh();
                $this->searchTable($browser, '@tablePrevalidation', $prevalidation->uid, $prevalidation->id);

                // Logout
                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * Assert that if a prevalidation already exists (same primary key), you cannot create another one.
     * @throws Throwable
     * @return void
     */
    public function testPrevalidationCreateWithSameKeyValidationError(): void
    {
        $implementation = Implementation::byKey('nijmegen');

        $fund = $this->makeTestFund($implementation->organization, fundConfigsData: ['csv_primary_key' => 'uid']);
        $this->addTestCriteriaToFund($fund);

        $uid = token_generator()->generate(32);
        $this->makePrevalidationForTestCriteria($implementation->organization, $fund, $uid);

        $this->rollbackModels([], function () use ($implementation, $fund, $uid) {
            $this->browse(function (Browser $browser) use ($implementation, $fund, $uid) {
                $browser->visit($implementation->urlSponsorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $implementation->organization->identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $implementation->organization->identity);
                $this->selectDashboardOrganization($browser, $implementation->organization);

                $this->goToPrevalidationsPage($browser, $fund);
                $this->openPrevalidationCreateModal($browser);

                $this->fillFormAndSubmit($browser, $fund, [[
                    'record_type_key' => 'uid',
                    'control_type' => 'text',
                    'assert_invalid' => $uid,
                ]], true);

                // Logout
                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * Test batch upload when CSV has 2 records: the first record already exists with identical records and primary key
     * in the database. The system skips the first one (does nothing) and creates the second one.
     * @throws Throwable
     * @return void
     */
    public function testPrevalidationBatchUploadCase1(): void
    {
        $implementation = Implementation::byKey('nijmegen');

        $fund = $this->makeTestFund($implementation->organization, fundConfigsData: ['csv_primary_key' => 'uid']);
        $this->addTestCriteriaToFund($fund);

        // create one prevalidation for future test prevalidation creation with the same primary key and records
        $existingPrevalidation = $this->makePrevalidationForTestCriteria($implementation->organization, $fund);

        // prepare prevalidation records for upload
        $existingPrevalidationData = $existingPrevalidation->prevalidation_records
            ->mapWithKeys(fn (PrevalidationRecord $record) => [$record->record_type->key => $record->value])
            ->toArray();

        // prepare new prevalidation with unique primary_key
        $newPrevalidationData = [
            'uid' => token_generator()->generate(32),
            'test_iban' => fake()->iban,
            'test_date' => '01-01-2010',
            'test_email' => fake()->email,
            'test_string_any' => 'lorem_ipsum',
            'test_number' => 7,
            'test_select' => 'foo',
        ];

        // sort records for same key order
        ksort($existingPrevalidationData);
        ksort($newPrevalidationData);

        $this->rollbackModels([], function () use (
            $implementation,
            $fund,
            $existingPrevalidation,
            $existingPrevalidationData,
            $newPrevalidationData,
        ) {
            $this->browse(function (Browser $browser) use (
                $implementation,
                $fund,
                $existingPrevalidation,
                $existingPrevalidationData,
                $newPrevalidationData,
            ) {
                $browser->visit($implementation->urlSponsorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $implementation->organization->identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $implementation->organization->identity);
                $this->selectDashboardOrganization($browser, $implementation->organization);

                $this->goToPrevalidationsPage($browser, $fund);
                $this->uploadPrevalidationsBatch($browser, [
                    $existingPrevalidationData,
                    $newPrevalidationData,
                ]);

                // assert first prevalidation didn't change
                $this->assertRecordsEquals($existingPrevalidation->refresh(), $existingPrevalidationData);

                // assert second prevalidation created
                $this->assertPrevalidationCreated($fund, $newPrevalidationData);

                // Logout
                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
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
        $implementation = Implementation::byKey('nijmegen');

        $fund = $this->makeTestFund($implementation->organization, fundConfigsData: ['csv_primary_key' => 'uid']);
        $this->addTestCriteriaToFund($fund);

        // create one prevalidation
        $existingPrevalidation = $this->makePrevalidationForTestCriteria($implementation->organization, $fund);

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
            'test_iban' => fake()->iban,
            'test_date' => '01-01-2010',
            'test_email' => fake()->email,
            'test_string_any' => 'lorem_ipsum',
            'test_number' => 7,
            'test_select' => 'foo',
        ];

        // sort records for same key order
        ksort($existingPrevalidationData);
        ksort($newPrevalidationData);

        $this->rollbackModels([], function () use (
            $implementation,
            $fund,
            $existingPrevalidation,
            $existingPrevalidationData,
            $newPrevalidationData
        ) {
            $this->browse(function (Browser $browser) use (
                $implementation,
                $fund,
                $existingPrevalidation,
                $existingPrevalidationData,
                $newPrevalidationData
            ) {
                $browser->visit($implementation->urlSponsorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $implementation->organization->identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $implementation->organization->identity);
                $this->selectDashboardOrganization($browser, $implementation->organization);

                $this->goToPrevalidationsPage($browser, $fund);

                $this->uploadPrevalidationsBatch($browser, [
                    $existingPrevalidationData,
                    $newPrevalidationData,
                ], true);

                // assert first prevalidation updated (as we changed $existingPrevalidationData)
                $this->assertRecordsEquals($existingPrevalidation->refresh(), $existingPrevalidationData);

                // assert second prevalidation created
                $this->assertPrevalidationCreated($fund, $newPrevalidationData);

                // Logout
                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * Test batch upload when CSV has 2 records: both already exist and haven't changed.
     * The system shows a success message and skips both records.
     * @throws Throwable
     * @return void
     */
    public function testPrevalidationBatchUploadCase3(): void
    {
        $implementation = Implementation::byKey('nijmegen');

        $fund = $this->makeTestFund($implementation->organization, fundConfigsData: ['csv_primary_key' => 'uid']);
        $this->addTestCriteriaToFund($fund);

        $firstPrevalidation = $this->makePrevalidationForTestCriteria($implementation->organization, $fund);
        $secondPrevalidation = $this->makePrevalidationForTestCriteria($implementation->organization, $fund);

        // prepare prevalidation records for upload
        $firstPrevalidationData = $firstPrevalidation->prevalidation_records
            ->mapWithKeys(fn (PrevalidationRecord $record) => [$record->record_type->key => $record->value])
            ->toArray();

        // prepare prevalidation records for upload
        $secondPrevalidationData = $secondPrevalidation->prevalidation_records
            ->mapWithKeys(fn (PrevalidationRecord $record) => [$record->record_type->key => $record->value])
            ->toArray();

        // sort records for same key order
        ksort($firstPrevalidationData);
        ksort($secondPrevalidationData);

        $this->rollbackModels([], function () use (
            $implementation,
            $fund,
            $firstPrevalidation,
            $firstPrevalidationData,
            $secondPrevalidation,
            $secondPrevalidationData,
        ) {
            $this->browse(function (Browser $browser) use (
                $implementation,
                $fund,
                $firstPrevalidation,
                $firstPrevalidationData,
                $secondPrevalidation,
                $secondPrevalidationData,
            ) {
                $browser->visit($implementation->urlSponsorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $implementation->organization->identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $implementation->organization->identity);
                $this->selectDashboardOrganization($browser, $implementation->organization);

                $this->goToPrevalidationsPage($browser, $fund);
                $this->uploadPrevalidationsBatch($browser, [
                    $firstPrevalidationData,
                    $secondPrevalidationData,
                ]);

                // assert first prevalidation didn't change
                $this->assertRecordsEquals($firstPrevalidation->refresh(), $firstPrevalidationData);

                // assert second prevalidation didn't change
                $this->assertRecordsEquals($secondPrevalidation->refresh(), $secondPrevalidationData);

                // Logout
                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @param Organization $organization
     * @param Fund $fund
     * @param string|null $primaryKey
     * @return Prevalidation
     */
    public function makePrevalidationForTestCriteria(
        Organization $organization,
        Fund $fund,
        ?string $primaryKey = null,
    ): Prevalidation {
        // create prevalidation
        $response = $this->apiMakeStorePrevalidationRequest($organization, $fund, [
            $this->makeRequestCriterionValue($fund, 'test_iban', fake()->iban),
            $this->makeRequestCriterionValue($fund, 'test_date', '01-01-2010'),
            $this->makeRequestCriterionValue($fund, 'test_email', fake()->email),
            $this->makeRequestCriterionValue($fund, 'test_string_any', 'ipsum_lorem'),
            $this->makeRequestCriterionValue($fund, 'test_number', 7),
            $this->makeRequestCriterionValue($fund, 'test_select', 'foo'),
        ], [
            $fund->fund_config->csv_primary_key => $primaryKey ?: token_generator()->generate(32),
        ]);

        $response->assertSuccessful();

        return Prevalidation::find($response->json('data.id'));
    }

    /**
     * @param Fund $fund
     * @return void
     */
    protected function addTestCriteriaToFund(Fund $fund): void
    {
        $fund->criteria->each(function (FundCriterion $criterion) {
            $criterion->fund_criterion_rules()->delete();
            $criterion->fund_request_record()->delete();
        });

        $fund->criteria()->forceDelete();

        RecordType::whereIn('key', [
            'test_iban', 'test_date', 'test_email', 'test_string_any', 'test_number', 'test_select',
        ])->delete();

        $this->makeRecordType($fund->organization, RecordType::TYPE_IBAN, 'test_iban');
        $this->makeRecordType($fund->organization, RecordType::TYPE_DATE, 'test_date');
        $this->makeRecordType($fund->organization, RecordType::TYPE_EMAIL, 'test_email');
        $this->makeRecordType($fund->organization, RecordType::TYPE_STRING, 'test_string_any');
        $this->makeRecordType($fund->organization, RecordType::TYPE_NUMBER, 'test_number');
        $this->makeRecordType($fund->organization, RecordType::TYPE_SELECT, 'test_select');

        $response = $this->updateCriteriaRequest([
            $this->makeCriterion('test_iban', null, '*'),
            $this->makeCriterion('test_date', '01-01-2000', '>=', '01-01-1990', '01-01-2020'),
            $this->makeCriterion('test_email', null, '*'),
            $this->makeCriterion('test_string_any', null, '*', 5, 20),
            $this->makeCriterion('test_number', '7', '>=', 5, 10),
            $this->makeCriterion('test_select', 'foo', '='),
        ], $fund);

        $fund->organization->forceFill([
            'fund_request_resolve_policy' => $fund->organization::FUND_REQUEST_POLICY_AUTO_REQUESTED,
        ])->save();

        $fund->refresh();
        $response->assertSuccessful();
        Cache::flush();
    }

    /**
     * @param Browser $browser
     * @param Fund $fund
     * @param array $data
     * @param bool $stopOnFirstError
     * @throws TimeoutException
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @return void
     */
    protected function fillFormAndSubmit(Browser $browser, Fund $fund, array $data, bool $stopOnFirstError = false): void
    {
        $browser->waitFor('@modalCreatePrevalidation');
        $browser->within('@modalCreatePrevalidation', function (Browser $browser) use ($fund) {
            $browser->element('@selectControlFunds')->click();

            $browser->waitFor("@selectControlFundItem$fund->id");
            $browser->element("@selectControlFundItem$fund->id")->click();
        });

        $this->fillInputs($browser, $data, $stopOnFirstError);

        if ($stopOnFirstError) {
            $browser->waitFor('@closeBtn');
            $browser->click('@closeBtn');

            return;
        }

        $browser->click('@submitBtn');
        $browser->waitFor('@previewValues');
        $browser->click('@submitBtn');

        $browser->waitFor('@prevalidationOverview');
        $browser->waitFor('@closeBtn');
        $browser->click('@closeBtn');
    }

    /**
     * @param Browser $browser
     * @param array $fields
     * @param bool $stopOnFirstError
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    protected function fillInputs(Browser $browser, array $fields, bool $stopOnFirstError = false): void
    {
        foreach ($fields as $field) {
            $selector = $this->getControlSelector($field['control_type']) . $field['record_type_key'];

            // assert invalid value if exists
            if ($field['assert_invalid'] ?? false) {
                $this->fillInput($browser, $selector, $field['control_type'], $field['assert_invalid']);

                $browser->click('@submitBtn');
                $browser->waitFor('@previewValues');
                $browser->click('@submitBtn');
                $browser->waitFor('.form-error');

                // clear input
                if (!in_array($field['control_type'], ['date', 'select'])) {
                    $this->clearField($browser, $selector);
                }

                if ($stopOnFirstError) {
                    return;
                }
            }

            // fill valid value
            $this->fillInput($browser, $selector, $field['control_type'], $field['assert_valid']);
        }
    }

    /**
     * @param string $control
     * @return string|null
     */
    protected function getControlSelector(string $control): ?string
    {
        return match ($control) {
            'date' => '@controlDate',
            'text' => '@controlText',
            'select' => '@selectControl',
            'number' => '@controlNumber',
            default => null
        };
    }

    /**
     * @param Browser $browser
     * @param string $selector
     * @param string $control
     * @param string|int|null $value
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @throws ElementClickInterceptedException
     * @return void
     */
    protected function fillInput(
        Browser $browser,
        string $selector,
        string $control,
        string|int|null $value
    ): void {
        switch ($control) {
            case 'select':
                $browser->waitFor($selector);
                $browser->click("$selector .select-control-search");
                $this->findOptionElement($browser, $selector, $value)->click();
                break;
            case 'number':
            case 'text':
                $browser->waitFor($selector);
                $browser->type($selector, $value);
                break;
            case 'checkbox':
                $value && $browser->waitFor($selector)->click($selector);
                break;
            case 'date':
                $browser->waitFor($selector);
                $this->clearField($browser, "$selector input[type='text']");
                $browser->type("$selector input[type='text']", $value);
                break;
        }
    }

    /**
     * @param Browser $browser
     * @throws \Facebook\WebDriver\Exception\TimeoutException
     * @throws \Facebook\WebDriver\Exception\ElementClickInterceptedException
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @return void
     */
    protected function openPrevalidationCreateModal(Browser $browser): void
    {
        $browser->waitFor('@createPrevalidationButton');
        $browser->click('@createPrevalidationButton');
    }

    /**
     * @param Browser $browser
     * @param array $prevalidationsData
     * @param bool $confirmUpdate
     * @throws TimeoutException
     * @return void
     */
    protected function uploadPrevalidationsBatch(
        Browser $browser,
        array $prevalidationsData,
        bool $confirmUpdate = false
    ): void {
        $browser->waitFor('@uploadPrevalidationsBatchButton');
        $browser->element('@uploadPrevalidationsBatchButton')->click();

        $browser->waitFor('@modalFundSelectSubmit');
        $browser->element('@modalFundSelectSubmit')->click();

        $browser->waitFor('@modalPrevalidationUpload');

        $browser->waitFor('@selectFileButton');
        $browser->element('@selectFileButton')->click();

        $this->createFile($prevalidationsData);
        $browser->attach('@inputUpload', Storage::path($this->csvPath));

        $browser->waitFor('@uploadFileButton');
        $browser->element('@uploadFileButton')->click();

        if ($confirmUpdate) {
            $browser->waitFor('@modalDuplicatesPicker');

            $browser->waitFor('@modalDuplicatesPickerConfirm');
            $browser->element('@modalDuplicatesPickerConfirm')->click();

            $browser->waitUntilMissing('@modalDuplicatesPicker');
        }

        $browser->waitFor('@successUploadIcon');

        $browser->element('@closeModalButton')->click();
        $browser->waitUntilMissing('@modalPrevalidationUpload');

        Storage::delete($this->csvPath);
    }

    /**
     * @param array $data
     * @return void
     */
    protected function createFile(array $data): void
    {
        $filename = Storage::path($this->csvPath);
        $handle = fopen($filename, 'w');

        fputcsv($handle, array_keys($data[0]));

        array_walk($data, fn ($item) => fputcsv($handle, $item));
        fclose($handle);
    }

    /**
     * @param Fund $fund
     * @param array $data
     * @return void
     */
    protected function assertPrevalidationCreated(Fund $fund, array $data): void
    {
        $record = RecordType::where('key', 'uid')->first();

        $prevalidation = Prevalidation::where('fund_id', $fund->id)
            ->whereHas('prevalidation_records', function (Builder $builder) use ($record, $data) {
                $builder->where('record_type_id', $record->id);
                $builder->where('value', $data['uid']);
            })
            ->first();

        $this->assertNotNull($prevalidation);
        $this->assertRecordsEquals($prevalidation, $data);
    }

    /**
     * @param Prevalidation $prevalidation
     * @param array $data
     * @return void
     */
    protected function assertRecordsEquals(Prevalidation $prevalidation, array $data): void
    {
        $records = $prevalidation->prevalidation_records;

        foreach ($data as $field => $value) {
            $record = $records->first(fn (PrevalidationRecord $record) => $record->record_type->key === $field);
            $this->assertNotNull($record);
            $this->assertEquals($value, $record->value);
        }
    }
}
