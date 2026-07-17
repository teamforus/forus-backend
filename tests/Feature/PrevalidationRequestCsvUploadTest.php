<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Fund;
use App\Models\FundCriterion;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\PrevalidationRequest;
use App\Models\PrevalidationRequestMissedRecord;
use App\Models\PrevalidationRequestRecord;
use App\Models\RecordType;
use App\Models\Role;
use App\Services\IConnectApiService\IConnectPrefill;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use Tests\Traits\MakesAssertStoreUploadedCsvFile;
use Tests\Traits\MakesTestFundRequestPrefills;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestIdentities;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestPrevalidationRequests;
use Throwable;

class PrevalidationRequestCsvUploadTest extends TestCase
{
    use MakesTestFunds;
    use MakesTestIdentities;
    use DatabaseTransactions;
    use MakesTestFundRequests;
    use MakesTestOrganizations;
    use MakesTestFundRequestPrefills;
    use MakesTestPrevalidationRequests;
    use MakesAssertStoreUploadedCsvFile;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('forus.person_bsn.test_response_profile', 'default');
        Config::set('forus.person_bsn.fund_prefill_cache_time', 0);
        Cache::flush();
    }

    /**
     *  Test success upload and different results of process uploaded requests:
     *   - success (prevalidation created)
     *   - iConnect connection error
     *   - iConnect not found by bsn
     *   - not all required criteria filled with prefills
     * @throws Throwable
     */
    public function testPrevalidationRequestCsvUploadSuccessAndAfterProcess(): void
    {
        [
            'organization' => $organization,
            'fund' => $fund,
            'manualKey' => $manualKey,
        ] = $this->makePrevalidationRequestCsvFixture([
            '159786575' => [
                'status' => 500,
                'body' => [],
            ],
            '159835562' => [
                'status' => 404,
                'body' => [],
            ],
            '216506414' => [
                'status' => 200,
                'body' => [],
            ],
        ]);

        $requestDataPrefillSuccess = $this->makePrevalidationRequestRow($manualKey);
        $requestDataPrefillFailConnectionError = $this->makePrevalidationRequestRow($manualKey, ['bsn' => '159786575']);
        $requestDataPrefillFailNotFound = $this->makePrevalidationRequestRow($manualKey, ['bsn' => '159835562']);
        $requestDataPrefillFailNotMetRequiredCriteria = $this->makePrevalidationRequestRow($manualKey, [
            'bsn' => '216506414',
        ]);

        $requestData = [
            'fund_id' => $fund->id,
            'data' => [
                $requestDataPrefillSuccess,
                $requestDataPrefillFailConnectionError,
                $requestDataPrefillFailNotFound,
                $requestDataPrefillFailNotMetRequiredCriteria,
            ],
        ];

        $response = $this->apiMakePrevalidationRequestCollectionRequest($organization, $requestData);

        $response->assertSuccessful();

        // assert prefill success and prevalidation was created
        $requestPrefillSuccess = $this->assertPrevalidationRequestCreated($fund, $requestDataPrefillSuccess);
        $requestPrefillSuccess->makePrevalidation();
        $this->assertNotNull($requestPrefillSuccess->prevalidation);
        $this->assertEquals(PrevalidationRequest::STATE_SUCCESS, $requestPrefillSuccess->state);

        // assert if IConnect gives connection error - request got failed and right reason was stored in logs
        $requestPrefillFailConnectionError = $this->assertPrevalidationRequestCreated($fund, $requestDataPrefillFailConnectionError);
        $requestPrefillFailConnectionError->makePrevalidation();
        $this->assertNull($requestPrefillFailConnectionError->prevalidation);
        $this->assertEquals(PrevalidationRequest::STATE_FAIL, $requestPrefillFailConnectionError->state);
        $this->assertEquals(IConnectPrefill::PREFILL_ERROR_CONNECTION_ERROR, $requestPrefillFailConnectionError->failed_reason);

        // assert if IConnect gives 404 - request got failed and right reason was stored in logs
        $requestPrefillFailNotFound = $this->assertPrevalidationRequestCreated($fund, $requestDataPrefillFailNotFound);
        $requestPrefillFailNotFound->makePrevalidation();
        $this->assertNull($requestPrefillFailNotFound->prevalidation);
        $this->assertEquals(PrevalidationRequest::STATE_FAIL, $requestPrefillFailNotFound->state);
        $this->assertEquals(IConnectPrefill::PREFILL_ERROR_NOT_FOUND, $requestPrefillFailNotFound->failed_reason);

        // assert if required criteria was not prefilled - request got failed and right reason was stored in logs
        $requestPrefillFailNotMetRequiredCriteria = $this->assertPrevalidationRequestCreated($fund, $requestDataPrefillFailNotMetRequiredCriteria);
        $requestPrefillFailNotMetRequiredCriteria->makePrevalidation();
        $this->assertNull($requestPrefillFailNotMetRequiredCriteria->prevalidation);
        $this->assertEquals(PrevalidationRequest::STATE_FAIL, $requestPrefillFailNotMetRequiredCriteria->state);
        $this->assertEquals(IConnectPrefill::PREFILL_ERROR_NOT_FILLED_REQUIRED_CRITERIA, $requestPrefillFailNotMetRequiredCriteria->failed_reason);
    }

    /**
     * @return void
     */
    public function testPrevalidationRequestCsvUploadValidation(): void
    {
        [
            'organization' => $organization,
            'fund' => $fund,
            'manualKey' => $manualKey,
        ] = $this->makePrevalidationRequestCsvFixture(includeRequiredIncomeGroup: true);

        // assert validation error for missing BSN
        $requestData = [
            'fund_id' => $fund->id,
            'data' => [[
                'uid' => token_generator()->generate(32),
                $manualKey => 3,
                'income_checkbox_paid_work' => 'Ja',
            ]],
        ];

        $this->apiMakePrevalidationRequestCollectionRequest($organization, $requestData)
            ->assertJsonValidationErrorFor('data.0.bsn');

        // assert validation error for missed required criterion
        $requestData = [
            'fund_id' => $fund->id,
            'data' => [[
                'bsn' => '999993112',
                'uid' => token_generator()->generate(32),
                'income_checkbox_paid_work' => 'Ja',
            ]],
        ];

        $this->apiMakePrevalidationRequestCollectionRequest($organization, $requestData)
            ->assertJsonValidationErrorFor('data');

        // assert validation error for wrong value of $manualKey criterion
        $requestData = [
            'fund_id' => $fund->id,
            'data' => [$this->makePrevalidationRequestRow($manualKey, [
                $manualKey => 'wrong',
                'income_checkbox_paid_work' => 'Ja',
            ])],
        ];

        $this->apiMakePrevalidationRequestCollectionRequest($organization, $requestData)
            ->assertJsonValidationErrorFor("data.0.$manualKey");

        // assert missed criteria from required group
        $requestData = [
            'fund_id' => $fund->id,
            'data' => [$this->makePrevalidationRequestRow($manualKey)],
        ];

        $this->apiMakePrevalidationRequestCollectionRequest($organization, $requestData)
            ->assertJsonValidationErrorFor('data.0.income_checkbox_paid_work');

        // assert validation error for same BSN
        $requestData = [
            'fund_id' => $fund->id,
            'data' => [$this->makePrevalidationRequestRow($manualKey, [
                'income_checkbox_paid_work' => 'Ja',
            ])],
        ];

        $this->apiMakePrevalidationRequestCollectionRequest($organization, $requestData)
            ->assertSuccessful();

        // submit one more time with same bsn and assert validation error for BSN
        $this->apiMakePrevalidationRequestCollectionRequest($organization, $requestData)
            ->assertJsonValidationErrorFor('data.0.bsn');
    }

    /**
     * @throws Throwable
     */
    public function testPrevalidationRequestResubmitAndDelete(): void
    {
        [
            'organization' => $organization,
            'fund' => $fund,
            'manualKey' => $manualKey,
        ] = $this->makePrevalidationRequestCsvFixture([
            '159786575' => [
                'status' => 500,
                'body' => [],
            ],
        ]);

        $requestDataPrefillFailConnectionError = $this->makePrevalidationRequestRow($manualKey, ['bsn' => '159786575']);

        $requestData = [
            'fund_id' => $fund->id,
            'data' => [$requestDataPrefillFailConnectionError],
        ];

        $this->apiMakePrevalidationRequestCollectionRequest($organization, $requestData)
            ->assertSuccessful();

        $request = $this->assertPrevalidationRequestCreated($fund, $requestDataPrefillFailConnectionError);

        // assert resubmit
        $this->apiPrevalidationRequestResubmitRequest($organization, $request)
            ->assertForbidden();

        $request->makePrevalidation();
        $request->refresh();
        $this->assertNull($request->prevalidation);
        $this->assertEquals(PrevalidationRequest::STATE_FAIL, $request->state);
        $this->assertEquals(IConnectPrefill::PREFILL_ERROR_CONNECTION_ERROR, $request->failed_reason);

        $this->apiPrevalidationRequestResubmitRequest($organization, $request)
            ->assertSuccessful();

        $request->refresh();
        $this->assertEquals(PrevalidationRequest::STATE_PENDING, $request->state);

        // assert deletion
        $this->apiPrevalidationRequestDeleteRequest($organization, $request)
            ->assertForbidden();

        $request->makePrevalidation();
        $request->refresh();
        $this->assertNull($request->prevalidation);
        $this->assertEquals(PrevalidationRequest::STATE_FAIL, $request->state);
        $this->assertEquals(IConnectPrefill::PREFILL_ERROR_CONNECTION_ERROR, $request->failed_reason);

        $this->apiPrevalidationRequestDeleteRequest($organization, $request)
            ->assertSuccessful();

        $this->assertNull(PrevalidationRequest::find($request->id));
    }

    /**
     * @throws Throwable
     */
    public function testPrevalidationRequestResubmitRefreshesOnlyUneditedBrpRecords(): void
    {
        $personResponse = Config::get('forus.person_bsn.test_response_data.default')[999993112];
        $personResponse['_embedded']['kinderen'] = [];

        $this->fakePersonBsnApiResponses([
            '999993112' => ['data' => $personResponse],
        ]);

        $organization = $this->makeTestOrganization($this->makeIdentity());
        $this->enablePersonBsnApiForOrganization($organization);
        $this->enablePrevalidationRequestForOrganization($organization);

        $fund = $this->makeTestFund($organization, [
            'type' => 'budget',
        ], [
            'allow_fund_request_prefill' => true,
            'allow_prevalidations' => false,
            'key' => 'nijmegen-vi',
        ]);

        $this->makeRecordTypeForKey(
            $organization,
            Fund::RECORD_TYPE_KEY_CHILDREN_SAME_ADDRESS,
            RecordType::TYPE_NUMBER,
            RecordType::CONTROL_TYPE_NUMBER,
        );

        $this->makeRecordTypeForKey(
            $organization,
            Fund::RECORD_TYPE_KEY_PARTNERS_SAME_ADDRESS,
            RecordType::TYPE_NUMBER,
            RecordType::CONTROL_TYPE_NUMBER,
        );

        $this->makeFundCriteria($fund, [[
            'title' => 'Children count',
            'value' => 1,
            'operator' => '>=',
            'optional' => true,
            'record_type_key' => Fund::RECORD_TYPE_KEY_CHILDREN_SAME_ADDRESS,
            'show_attachment' => false,
            'fill_type' => FundCriterion::FILL_TYPE_PREFILL,
        ], [
            'title' => 'Partner count',
            'value' => 1,
            'operator' => '>=',
            'optional' => true,
            'record_type_key' => Fund::RECORD_TYPE_KEY_PARTNERS_SAME_ADDRESS,
            'show_attachment' => false,
            'fill_type' => FundCriterion::FILL_TYPE_PREFILL,
        ]]);

        $requestDataPrefill = [
            'bsn' => '999993112',
            'uid' => token_generator()->generate(32),
        ];

        $response = $this->apiMakePrevalidationRequestCollectionRequest($organization, [
            'fund_id' => $fund->id,
            'data' => [$requestDataPrefill],
        ]);

        $response->assertSuccessful();

        $prevalidationRequest = $this->assertPrevalidationRequestCreated($fund, $requestDataPrefill);
        $employee = $organization->employees()->first();

        $childrenRecord = $prevalidationRequest->records()->create([
            'record_type_key' => Fund::RECORD_TYPE_KEY_CHILDREN_SAME_ADDRESS,
            'value' => 'invalid',
            'source' => PrevalidationRequestRecord::SOURCE_BRP,
        ]);

        $partnersRecord = $prevalidationRequest->records()->create([
            'record_type_key' => Fund::RECORD_TYPE_KEY_PARTNERS_SAME_ADDRESS,
            'value' => 'invalid',
            'source' => PrevalidationRequestRecord::SOURCE_BRP,
        ]);

        $childrenRecord->change('3', $employee);
        $this->assertNotEmpty($childrenRecord->refresh()->historyLogs());

        $prevalidationRequest->update(['state' => PrevalidationRequest::STATE_FAIL]);
        $prevalidationRequest->resubmit();
        $prevalidationRequest->makePrevalidation();

        $prevalidationRequest->refresh();

        $this->assertEquals(PrevalidationRequest::STATE_SUCCESS, $prevalidationRequest->state);
        $this->assertNotNull($prevalidationRequest->prevalidation);
        $this->assertEquals('3', $childrenRecord->refresh()->value);
        $this->assertEquals(2, $partnersRecord->refresh()->value);
    }

    /**
     * @throws Throwable
     */
    public function testPrevalidationRequestMissingRecords(): void
    {
        [
            'organization' => $organization,
            'prevalidationRequest' => $prevalidationRequest,
        ] = $this->makePrevalidationRequestWithMissingRecords();

        $missedRecords = $this->assertPrevalidationRequestMissingRecordsState($prevalidationRequest);

        // assert person birth_date as missed record presents
        $personBirthDate = $missedRecords->first(fn (PrevalidationRequestMissedRecord $record) => (
            $record->group === 'person' && $record->field === 'birth_date'
        ));

        $this->assertNotNull($personBirthDate);
        $this->assertEquals(PrevalidationRequestMissedRecord::TYPE_WARNING, $personBirthDate->type);

        // approve missed records
        $response = $this->apiPrevalidationRequestApproveMissedRecordsRequest($organization, $prevalidationRequest, [
            'note' => 'test note',
        ]);

        $response->assertSuccessful();
        $prevalidationRequest->refresh();

        $this->assertEquals($prevalidationRequest::STATE_MISSING_RECORDS, $prevalidationRequest->state);
        $this->assertNull($prevalidationRequest->prevalidation);
        $this->assertTrue($prevalidationRequest->missing_records_approved);
        $this->assertMissedRecordsApprovalNoteExists($prevalidationRequest, 'test note');

        // create prevalidation after approval
        $response = $this->apiPrevalidationRequestFinalizeRequest($organization, $prevalidationRequest);

        $response->assertSuccessful();
        $prevalidationRequest->refresh();

        $this->assertEquals($prevalidationRequest::STATE_SUCCESS, $prevalidationRequest->state);
        $this->assertNotNull($prevalidationRequest->prevalidation);
        $this->assertTrue($prevalidationRequest->missing_records_approved);
    }

    /**
     * @throws Throwable
     */
    public function testPrevalidationRequestFinalizeRequiresMissingRecordsApproval(): void
    {
        [
            'organization' => $organization,
            'prevalidationRequest' => $prevalidationRequest,
        ] = $this->makePrevalidationRequestWithMissingRecords();

        $this->apiPrevalidationRequestFinalizeRequest($organization, $prevalidationRequest)
            ->assertForbidden();

        $prevalidationRequest->refresh();

        $this->assertPrevalidationRequestMissingRecordsState($prevalidationRequest);
    }

    /**
     * @throws Throwable
     */
    public function testPrevalidationRequestNoteDeleteRequiresNoteToBelongToRequest(): void
    {
        [
            'organization' => $organization,
            'fund' => $fund,
            'prevalidationRequest' => $prevalidationRequest,
        ] = $this->makePrevalidationRequestWithMissingRecords();

        $employee = $organization->findEmployee($organization->identity_address);

        $otherPrevalidationRequest = $organization->prevalidation_requests()->create([
            'bsn' => '999994542',
            'state' => PrevalidationRequest::STATE_FAIL,
            'fund_id' => $fund->id,
            'employee_id' => $employee->id,
        ]);

        $note = $otherPrevalidationRequest->addNote('Other prevalidation request note', $employee);

        $this->apiDeletePrevalidationRequestNoteRequest($organization, $prevalidationRequest, $note)
            ->assertForbidden();

        $this->assertNotNull($otherPrevalidationRequest->notes()->find($note->id));
    }

    /**
     * @throws Throwable
     */
    public function testPrevalidationRequestNoteDescriptionHasMaxLength(): void
    {
        [
            'organization' => $organization,
            'prevalidationRequest' => $prevalidationRequest,
        ] = $this->makePrevalidationRequestWithMissingRecords();

        $response = $this->apiStorePrevalidationRequestNoteRequest($organization, $prevalidationRequest, [
            'description' => str_repeat('a', 2001),
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('description');

        $this->assertCount(0, $prevalidationRequest->fresh()->notes);
    }

    /**
     * @throws Throwable
     */
    public function testPrevalidationRequestFinalizeFailurePreservesMissingRecordsApproval(): void
    {
        [
            'organization' => $organization,
            'prevalidationRequest' => $prevalidationRequest,
        ] = $this->makePrevalidationRequestWithMissingRecords();

        $record = $prevalidationRequest
            ->records()
            ->where('record_type_key', Fund::RECORD_TYPE_KEY_CHILDREN_SAME_ADDRESS)
            ->first();

        $record->update(['value' => 'invalid']);

        $this->apiPrevalidationRequestApproveMissedRecordsRequest($organization, $prevalidationRequest, [
            'note' => 'test note',
        ])->assertSuccessful();

        $prevalidationRequest->refresh();

        $this->assertEquals($prevalidationRequest::STATE_MISSING_RECORDS, $prevalidationRequest->state);
        $this->assertNull($prevalidationRequest->prevalidation);
        $this->assertTrue($prevalidationRequest->missing_records_approved);
        $this->assertMissedRecordsApprovalNoteExists($prevalidationRequest, 'test note');

        $this->apiPrevalidationRequestFinalizeRequest($organization, $prevalidationRequest)
            ->assertSuccessful();

        $prevalidationRequest->refresh();

        $this->assertEquals($prevalidationRequest::STATE_FAIL, $prevalidationRequest->state);
        $this->assertNull($prevalidationRequest->prevalidation);
        $this->assertTrue($prevalidationRequest->missing_records_approved);
        $this->assertEquals($prevalidationRequest::FAILED_REASON_INVALID_RECORDS, $prevalidationRequest->failed_reason);
        $this->assertMissedRecordsApprovalNoteExists($prevalidationRequest, 'test note');

        $record->update(['value' => 3]);

        $this->apiPrevalidationRequestFinalizeRequest($organization, $prevalidationRequest)
            ->assertSuccessful();

        $prevalidationRequest->refresh();

        $this->assertEquals($prevalidationRequest::STATE_SUCCESS, $prevalidationRequest->state);
        $this->assertNotNull($prevalidationRequest->prevalidation);
        $this->assertTrue($prevalidationRequest->missing_records_approved);
    }

    /**
     * @throws Throwable
     */
    public function testPrevalidationRequestRecordEditWithMissingRecords(): void
    {
        [
            'organization' => $organization,
            'prevalidationRequest' => $prevalidationRequest,
        ] = $this->makePrevalidationRequestWithMissingRecords();

        $this->assertPrevalidationRequestMissingRecordsState($prevalidationRequest);

        // find record and assert sponsor can edit this record while request state not success
        $record = $prevalidationRequest->records()->where(
            'record_type_key',
            Fund::RECORD_TYPE_KEY_CHILDREN_SAME_ADDRESS
        )->first();
        $this->assertNotNull($record);
        $this->assertEquals(2, $record->value);

        $this->apiUpdatePrevalidationRequestRecordRequest($organization, $prevalidationRequest, $record, [
            'value' => 3,
        ])->assertSuccessful();

        $this->assertEquals(3, $record->refresh()->value);

        // approve missed records
        $response = $this->apiPrevalidationRequestApproveMissedRecordsRequest($organization, $prevalidationRequest, [
            'note' => 'test note',
        ]);

        $response->assertSuccessful();
        $prevalidationRequest->refresh();

        $this->assertEquals($prevalidationRequest::STATE_MISSING_RECORDS, $prevalidationRequest->state);
        $this->assertNull($prevalidationRequest->prevalidation);
        $this->assertTrue($prevalidationRequest->missing_records_approved);

        // create prevalidation after approval
        $response = $this->apiPrevalidationRequestFinalizeRequest($organization, $prevalidationRequest);

        $response->assertSuccessful();
        $prevalidationRequest->refresh();

        $this->assertEquals($prevalidationRequest::STATE_SUCCESS, $prevalidationRequest->state);
        $this->assertNotNull($prevalidationRequest->prevalidation);
        $this->assertTrue($prevalidationRequest->missing_records_approved);

        // assert you can not edit record if prevalidation request already processed
        $this->apiUpdatePrevalidationRequestRecordRequest($organization, $prevalidationRequest, $record, [
            'value' => 3,
        ])->assertForbidden();
    }

    /**
     * @throws Throwable
     */
    public function testPrevalidationRequestDirectAccessUsesVisibleScope(): void
    {
        [
            'organization' => $organization,
            'prevalidationRequest' => $prevalidationRequest,
        ] = $this->makePrevalidationRequestWithMissingRecords();

        $otherValidator = $this->makeEmployeeWithPermissions($organization, [Permission::VALIDATE_RECORDS]);
        $record = $prevalidationRequest->records()->where(
            'record_type_key',
            Fund::RECORD_TYPE_KEY_CHILDREN_SAME_ADDRESS,
        )->first();

        $note = $prevalidationRequest->addNote(
            'Owner note',
            $organization->findEmployee($organization->identity_address),
        );

        $this->apiGetPrevalidationRequestsRequest($organization, $otherValidator->identity)
            ->assertSuccessful()
            ->assertJsonPath('data.*.id', fn (array $ids) => !in_array($prevalidationRequest->id, $ids, true));

        $this->apiGetPrevalidationRequestRequest(
            $organization,
            $prevalidationRequest,
            $otherValidator->identity,
        )->assertForbidden();

        $this->apiGetPrevalidationRequestPersonRequest(
            $organization,
            $prevalidationRequest,
            $otherValidator->identity,
        )->assertForbidden();

        $this->apiGetPrevalidationRequestNotesRequest(
            $organization,
            $prevalidationRequest,
            $otherValidator->identity,
        )->assertForbidden();

        $this->apiStorePrevalidationRequestNoteRequest(
            $organization,
            $prevalidationRequest,
            ['description' => 'Other validator note'],
            $otherValidator->identity,
        )->assertForbidden();

        $this->apiDeletePrevalidationRequestNoteRequest(
            $organization,
            $prevalidationRequest,
            $note,
            $otherValidator->identity,
        )->assertForbidden();

        $this->apiUpdatePrevalidationRequestRecordRequest(
            $organization,
            $prevalidationRequest,
            $record,
            ['value' => 3],
            $otherValidator->identity,
        )->assertForbidden();

        $this->apiPrevalidationRequestApproveMissedRecordsRequest(
            $organization,
            $prevalidationRequest,
            ['note' => 'Other validator approval'],
            $otherValidator->identity,
        )->assertForbidden();

        $prevalidationRequest->update(['missing_records_approved' => true]);

        $this->apiPrevalidationRequestFinalizeRequest(
            $organization,
            $prevalidationRequest,
            $otherValidator->identity,
        )->assertForbidden();

        $prevalidationRequest->update([
            'state' => PrevalidationRequest::STATE_FAIL,
            'missing_records_approved' => false,
        ]);

        $this->apiPrevalidationRequestResubmitRequest(
            $organization,
            $prevalidationRequest,
            $otherValidator->identity,
        )->assertForbidden();

        $this->apiPrevalidationRequestDeleteRequest(
            $organization,
            $prevalidationRequest,
            $otherValidator->identity,
        )->assertForbidden();
    }

    /**
     * @throws Throwable
     */
    public function testPrevalidationRequestManagerCanAccessVisibleRequests(): void
    {
        [
            'organization' => $organization,
            'prevalidationRequest' => $prevalidationRequest,
        ] = $this->makePrevalidationRequestWithMissingRecords();

        $manager = $this->makeEmployeeWithPermissions($organization, [Permission::MANAGE_ORGANIZATION]);

        $record = $prevalidationRequest->records()->where(
            'record_type_key',
            Fund::RECORD_TYPE_KEY_CHILDREN_SAME_ADDRESS,
        )->first();

        $this->apiGetPrevalidationRequestsRequest($organization, $manager->identity)
            ->assertSuccessful()
            ->assertJsonPath('data.*.id', fn (array $ids) => in_array($prevalidationRequest->id, $ids, true));

        $this->apiGetPrevalidationRequestRequest($organization, $prevalidationRequest, $manager->identity)
            ->assertSuccessful()
            ->assertJsonPath('data.id', $prevalidationRequest->id);

        $this->apiGetPrevalidationRequestPersonRequest($organization, $prevalidationRequest, $manager->identity)
            ->assertSuccessful()
            ->assertJsonPath('data.bsn', '999993112');

        $this->apiUpdatePrevalidationRequestRecordRequest(
            $organization,
            $prevalidationRequest,
            $record,
            ['value' => 3],
            $manager->identity,
        )->assertSuccessful();

        $this->apiStorePrevalidationRequestNoteRequest(
            $organization,
            $prevalidationRequest,
            ['description' => 'Manager note'],
            $manager->identity,
        )->assertSuccessful();

        $this->apiPrevalidationRequestApproveMissedRecordsRequest(
            $organization,
            $prevalidationRequest,
            ['note' => 'Manager approval'],
            $manager->identity,
        )->assertSuccessful();

        $this->apiPrevalidationRequestFinalizeRequest(
            $organization,
            $prevalidationRequest,
            $manager->identity,
        )->assertSuccessful();

        $prevalidationRequest->refresh();

        $this->assertEquals(PrevalidationRequest::STATE_SUCCESS, $prevalidationRequest->state);
        $this->assertNotNull($prevalidationRequest->prevalidation);
    }

    /**
     * @throws Throwable
     */
    public function testPrevalidationRequestFailedCountAndBulkResubmitUseVisibleScope(): void
    {
        [
            'organization' => $organization,
            'fund' => $fund,
        ] = $this->makePrevalidationRequestWithMissingRecords();

        $ownerEmployee = $organization->findEmployee($organization->identity_address);
        $otherValidator = $this->makeEmployeeWithPermissions($organization, [Permission::VALIDATE_RECORDS]);

        $ownerRequest = $this->makeFailedPrevalidationRequest($organization, $fund, $ownerEmployee, '999994542');
        $otherValidatorRequest = $this->makeFailedPrevalidationRequest($organization, $fund, $otherValidator, '999995807');

        $this->apiGetPrevalidationRequestsRequest($organization, $otherValidator->identity)
            ->assertSuccessful()
            ->assertJsonPath('meta.failed_count', 1)
            ->assertJsonPath('data.*.id', fn (array $ids) => $ids === [$otherValidatorRequest->id]);

        $this->apiPrevalidationRequestResubmitFailedRequest($organization, $otherValidator->identity)
            ->assertSuccessful();

        $this->assertEquals(PrevalidationRequest::STATE_FAIL, $ownerRequest->refresh()->state);
        $this->assertEquals(PrevalidationRequest::STATE_PENDING, $otherValidatorRequest->refresh()->state);
    }

    /**
     * @throws Throwable
     */
    public function testPrevalidationRequestStoreUploadedCsvFile(): void
    {
        [
            'organization' => $organization,
            'fund' => $fund,
            'manualKey' => $manualKey,
        ] = $this->makePrevalidationRequestCsvFixture([
            '159835562' => [
                'status' => 404,
                'body' => [],
            ],
        ]);

        $requestDataPrefillSuccess = $this->makePrevalidationRequestRow($manualKey);
        $requestDataPrefillFailNotFound = $this->makePrevalidationRequestRow($manualKey, ['bsn' => '159835562']);

        $requestData = [
            'fund_id' => $fund->id,
            'data' => [
                $requestDataPrefillSuccess,
                $requestDataPrefillFailNotFound,
            ],
        ];

        $this->apiMakePrevalidationRequestCollectionRequest(
            $organization,
            $requestData,
            appendFileData: true
        )->assertSuccessful();

        $employee = $organization->findEmployee($organization->identity);
        $log = $this->assertLogCreated($employee, $employee::EVENT_UPLOADED_PREVALIDATION_REQUESTS, 2);

        $this->assertLoggedUploadedFileContent($log, $requestData['data']);
    }

    /**
     * @param string $manualKey
     * @param array $overrides
     * @return array
     */
    private function makePrevalidationRequestRow(string $manualKey, array $overrides = []): array
    {
        return array_replace([
            'bsn' => '999993112',
            'uid' => token_generator()->generate(32),
            $manualKey => 3,
        ], $overrides);
    }

    /**
     * @param PrevalidationRequest $prevalidationRequest
     * @param string $note
     * @return void
     */
    private function assertMissedRecordsApprovalNoteExists(
        PrevalidationRequest $prevalidationRequest,
        string $note,
    ): void {
        $this->assertTrue($prevalidationRequest->notes()->where(
            'description',
            trans('fund_request.missed_records_approved') . "\n\n" . $note,
        )->exists());
    }

    /**
     * @param Organization $organization
     * @param array $permissions
     * @return Employee
     */
    private function makeEmployeeWithPermissions(Organization $organization, array $permissions): Employee
    {
        $role = Role::create(['key' => token_generator()->generate(32)]);
        $role->attachPermissions($permissions);

        return $organization->addEmployee(
            $this->makeIdentity($this->makeUniqueEmail()),
            [$role->id],
        );
    }

    /**
     * @param Organization $organization
     * @param Fund $fund
     * @param Employee $employee
     * @param string $bsn
     * @return PrevalidationRequest
     */
    private function makeFailedPrevalidationRequest(
        Organization $organization,
        Fund $fund,
        Employee $employee,
        string $bsn,
    ): PrevalidationRequest {
        $request = $organization->prevalidation_requests()->create([
            'bsn' => $bsn,
            'state' => PrevalidationRequest::STATE_FAIL,
            'fund_id' => $fund->id,
            'employee_id' => $employee->id,
        ]);

        $request->log(PrevalidationRequest::EVENT_FAILED, [
            'prevalidation_request' => $request,
            'organization' => $organization,
        ], [
            'prevalidation_request_fail_reason' => IConnectPrefill::PREFILL_ERROR_CONNECTION_ERROR,
        ], $employee->identity_address);

        return $request;
    }
}
