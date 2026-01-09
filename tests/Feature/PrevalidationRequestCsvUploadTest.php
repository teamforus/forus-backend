<?php

namespace Tests\Feature;

use App\Models\Fund;
use App\Models\FundCriterion;
use App\Models\Identity;
use App\Models\PrevalidationRequest;
use App\Models\RecordType;
use App\Services\IConnectApiService\IConnectPrefill;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
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

    protected function setUp(): void
    {
        parent::setUp();

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
        // prepare fake responses
        $this->fakePersonBsnApiResponses([
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

        // create organization and fund with prefills enabled
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

        // create record types and person-field mapping for prefills
        $prefillKey = token_generator()->generate(16);
        $manualKey = token_generator()->generate(16);
        $this->makePrefillRecordType($organization, $prefillKey, 'naam.geslachtsnaam');

        $this->makeRecordTypeForKey(
            $organization,
            $manualKey,
            RecordType::TYPE_NUMBER,
            RecordType::CONTROL_TYPE_NUMBER,
        );

        $this->makeRecordTypeForKey(
            $organization,
            Fund::RECORD_TYPE_KEY_PARTNERS_SAME_ADDRESS,
            RecordType::TYPE_NUMBER,
            RecordType::CONTROL_TYPE_NUMBER,
        );

        $this->makeRecordTypeForKey(
            $organization,
            Fund::RECORD_TYPE_KEY_CHILDREN_SAME_ADDRESS,
            RecordType::TYPE_NUMBER,
            RecordType::CONTROL_TYPE_NUMBER,
        );

        $criteria = [[
            'title' => 'Prefill last name',
            'value' => 'any',
            'operator' => '*',
            'optional' => false,
            'record_type_key' => $prefillKey,
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
        ], [
            'title' => 'Children count',
            'value' => 1,
            'operator' => '>=',
            'optional' => true,
            'record_type_key' => Fund::RECORD_TYPE_KEY_CHILDREN_SAME_ADDRESS,
            'show_attachment' => false,
            'fill_type' => FundCriterion::FILL_TYPE_PREFILL,
        ], [
            'title' => 'Manual number',
            'value' => 1,
            'operator' => '>=',
            'optional' => false,
            'record_type_key' => $manualKey,
            'show_attachment' => false,
        ]];

        $this->makeFundCriteria($fund, $criteria);

        $requestDataPrefillSuccess = [
            'bsn' => '999993112',
            'uid' => token_generator()->generate(32),
            $manualKey => 3,
        ];

        $requestDataPrefillFailConnectionError = [
            'bsn' => '159786575',
            'uid' => token_generator()->generate(32),
            $manualKey => 3,
        ];

        $requestDataPrefillFailNotFound = [
            'bsn' => '159835562',
            'uid' => token_generator()->generate(32),
            $manualKey => 3,
        ];

        $requestDataPrefillFailNotMetRequiredCriteria = [
            'bsn' => '216506414',
            'uid' => token_generator()->generate(32),
            $manualKey => 3,
        ];

        $requestData = [
            'fund_id' => $fund->id,
            'data' => [
                $requestDataPrefillSuccess,
                $requestDataPrefillFailConnectionError,
                $requestDataPrefillFailNotFound,
                $requestDataPrefillFailNotMetRequiredCriteria,
            ],
        ];

        $response = $this->postJson(
            "/api/v1/platform/organizations/$organization->id/prevalidation-requests/collection",
            $requestData,
            $this->makeApiHeaders($this->makeIdentityProxy($organization->identity)),
        );

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
        // prepare fake responses
        $this->fakePersonBsnApiResponses();

        // create organization and fund with prefills enabled
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

        // create record types and person-field mapping for prefills
        $prefillKey = token_generator()->generate(16);
        $manualKey = token_generator()->generate(16);
        $this->makePrefillRecordType($organization, $prefillKey, 'naam.geslachtsnaam');

        $this->makeRecordTypeForKey(
            $organization,
            $manualKey,
            RecordType::TYPE_NUMBER,
            RecordType::CONTROL_TYPE_NUMBER,
        );

        $this->makeRecordTypeForKey(
            $organization,
            Fund::RECORD_TYPE_KEY_PARTNERS_SAME_ADDRESS,
            RecordType::TYPE_NUMBER,
            RecordType::CONTROL_TYPE_NUMBER,
        );

        $this->makeRecordTypeForKey(
            $organization,
            Fund::RECORD_TYPE_KEY_CHILDREN_SAME_ADDRESS,
            RecordType::TYPE_NUMBER,
            RecordType::CONTROL_TYPE_NUMBER,
        );

        $this->makeRecordTypeForKey(
            $organization,
            'income_checkbox_paid_work',
            RecordType::TYPE_BOOL,
            RecordType::CONTROL_TYPE_CHECKBOX,
        );

        $this->makeRecordTypeForKey(
            $organization,
            'income_checkbox_subsidy',
            RecordType::TYPE_BOOL,
            RecordType::CONTROL_TYPE_CHECKBOX,
        );

        $incomeGroup = $this->makeCriteriaGroup(
            $fund,
            title: 'Enter how much income you and/or your partner have.',
            description: 'Vink aan welke inkomsten u en/of uw partner hebben gehad',
            required: true,
        );

        $criteria = [[
            'title' => 'Prefill last name',
            'value' => 'any',
            'operator' => '*',
            'optional' => false,
            'record_type_key' => $prefillKey,
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
        ], [
            'title' => 'Children count',
            'value' => 1,
            'operator' => '>=',
            'optional' => true,
            'record_type_key' => Fund::RECORD_TYPE_KEY_CHILDREN_SAME_ADDRESS,
            'show_attachment' => false,
            'fill_type' => FundCriterion::FILL_TYPE_PREFILL,
        ], [
            'title' => 'Manual number',
            'value' => 1,
            'operator' => '>=',
            'optional' => false,
            'record_type_key' => $manualKey,
            'show_attachment' => false,
        ], [
            'title' => 'Paid work (Wages)',
            'label' => 'Paid work (Wages)',
            'description' => '',
            'record_type_key' => 'income_checkbox_paid_work',
            'operator' => '*',
            'value' => 'Ja',
            'show_attachment' => true,
            'optional' => true,
            'fund_criteria_group_id' => $incomeGroup->id,
        ], [
            'title' => 'Subsidy',
            'label' => 'Subsidy',
            'description' => '',
            'record_type_key' => 'income_checkbox_subsidy',
            'operator' => '*',
            'value' => 'Ja',
            'show_attachment' => true,
            'optional' => true,
            'fund_criteria_group_id' => $incomeGroup->id,
        ]];

        $this->makeFundCriteria($fund, $criteria);

        // assert validation error for missing BSN
        $requestData = [
            'fund_id' => $fund->id,
            'data' => [[
                'uid' => token_generator()->generate(32),
                $manualKey => 3,
                'income_checkbox_paid_work' => 'Ja',
            ]],
        ];

        $this->postJson(
            "/api/v1/platform/organizations/$organization->id/prevalidation-requests/collection",
            $requestData,
            $this->makeApiHeaders($this->makeIdentityProxy($organization->identity)),
        )->assertJsonValidationErrorFor('data.0.bsn');

        // assert validation error for missed required criterion
        $requestData = [
            'fund_id' => $fund->id,
            'data' => [[
                'bsn' => '999993112',
                'uid' => token_generator()->generate(32),
                'income_checkbox_paid_work' => 'Ja',
            ]],
        ];

        $this->postJson(
            "/api/v1/platform/organizations/$organization->id/prevalidation-requests/collection",
            $requestData,
            $this->makeApiHeaders($this->makeIdentityProxy($organization->identity)),
        )->assertJsonValidationErrorFor('data');

        // assert validation error for wrong value of $manualKey criterion
        $requestData = [
            'fund_id' => $fund->id,
            'data' => [[
                'bsn' => '999993112',
                'uid' => token_generator()->generate(32),
                $manualKey => 'wrong',
                'income_checkbox_paid_work' => 'Ja',
            ]],
        ];

        $this->postJson(
            "/api/v1/platform/organizations/$organization->id/prevalidation-requests/collection",
            $requestData,
            $this->makeApiHeaders($this->makeIdentityProxy($organization->identity)),
        )->assertJsonValidationErrorFor("data.0.$manualKey");

        // assert missed criteria from required group
        $requestData = [
            'fund_id' => $fund->id,
            'data' => [[
                'bsn' => '999993112',
                'uid' => token_generator()->generate(32),
                $manualKey => 3,
            ]],
        ];

        $this->postJson(
            "/api/v1/platform/organizations/$organization->id/prevalidation-requests/collection",
            $requestData,
            $this->makeApiHeaders($this->makeIdentityProxy($organization->identity)),
        )->assertJsonValidationErrorFor('data.0.income_checkbox_paid_work');

        // assert validation error for same BSN
        $requestData = [
            'fund_id' => $fund->id,
            'data' => [[
                'bsn' => '999993112',
                'uid' => token_generator()->generate(32),
                $manualKey => 3,
                'income_checkbox_paid_work' => 'Ja',
            ]],
        ];

        $this->postJson(
            "/api/v1/platform/organizations/$organization->id/prevalidation-requests/collection",
            $requestData,
            $this->makeApiHeaders($this->makeIdentityProxy($organization->identity)),
        )->assertSuccessful();

        // submit one more time with same bsn and assert validation error for BSN
        $this->postJson(
            "/api/v1/platform/organizations/$organization->id/prevalidation-requests/collection",
            $requestData,
            $this->makeApiHeaders($this->makeIdentityProxy($organization->identity)),
        )->assertJsonValidationErrorFor('data.0.bsn');
    }

    /**
     * @throws Throwable
     */
    public function testPrevalidationRequestResubmitAndDelete(): void
    {
        // prepare fake responses
        $this->fakePersonBsnApiResponses([
            '159786575' => [
                'status' => 500,
                'body' => [],
            ],
        ]);

        // create organization and fund with prefills enabled
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

        // create record types and person-field mapping for prefills
        $prefillKey = token_generator()->generate(16);
        $manualKey = token_generator()->generate(16);
        $this->makePrefillRecordType($organization, $prefillKey, 'naam.geslachtsnaam');

        $this->makeRecordTypeForKey(
            $organization,
            $manualKey,
            RecordType::TYPE_NUMBER,
            RecordType::CONTROL_TYPE_NUMBER,
        );

        $this->makeRecordTypeForKey(
            $organization,
            Fund::RECORD_TYPE_KEY_PARTNERS_SAME_ADDRESS,
            RecordType::TYPE_NUMBER,
            RecordType::CONTROL_TYPE_NUMBER,
        );

        $this->makeRecordTypeForKey(
            $organization,
            Fund::RECORD_TYPE_KEY_CHILDREN_SAME_ADDRESS,
            RecordType::TYPE_NUMBER,
            RecordType::CONTROL_TYPE_NUMBER,
        );

        $criteria = [[
            'title' => 'Prefill last name',
            'value' => 'any',
            'operator' => '*',
            'optional' => false,
            'record_type_key' => $prefillKey,
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
        ], [
            'title' => 'Children count',
            'value' => 1,
            'operator' => '>=',
            'optional' => true,
            'record_type_key' => Fund::RECORD_TYPE_KEY_CHILDREN_SAME_ADDRESS,
            'show_attachment' => false,
            'fill_type' => FundCriterion::FILL_TYPE_PREFILL,
        ], [
            'title' => 'Manual number',
            'value' => 1,
            'operator' => '>=',
            'optional' => false,
            'record_type_key' => $manualKey,
            'show_attachment' => false,
        ]];

        $this->makeFundCriteria($fund, $criteria);

        $requestDataPrefillFailConnectionError = [
            'bsn' => '159786575',
            'uid' => token_generator()->generate(32),
            $manualKey => 3,
        ];

        $requestData = [
            'fund_id' => $fund->id,
            'data' => [$requestDataPrefillFailConnectionError],
        ];

        $this->postJson(
            "/api/v1/platform/organizations/$organization->id/prevalidation-requests/collection",
            $requestData,
            $this->makeApiHeaders($this->makeIdentityProxy($organization->identity)),
        )->assertSuccessful();

        $request = $this->assertPrevalidationRequestCreated($fund, $requestDataPrefillFailConnectionError);

        // assert resubmit
        $this->getJson(
            "/api/v1/platform/organizations/$organization->id/prevalidation-requests/$request->id/resubmit",
            $this->makeApiHeaders($this->makeIdentityProxy($organization->identity)),
        )->assertForbidden();

        $request->makePrevalidation();
        $request->refresh();
        $this->assertNull($request->prevalidation);
        $this->assertEquals(PrevalidationRequest::STATE_FAIL, $request->state);
        $this->assertEquals(IConnectPrefill::PREFILL_ERROR_CONNECTION_ERROR, $request->failed_reason);

        $this->getJson(
            "/api/v1/platform/organizations/$organization->id/prevalidation-requests/$request->id/resubmit",
            $this->makeApiHeaders($this->makeIdentityProxy($organization->identity)),
        )->assertSuccessful();

        $request->refresh();
        $this->assertEquals(PrevalidationRequest::STATE_PENDING, $request->state);

        // assert deletion
        $this->deleteJson(
            "/api/v1/platform/organizations/$organization->id/prevalidation-requests/$request->id",
            headers: $this->makeApiHeaders($this->makeIdentityProxy($organization->identity)),
        )->assertForbidden();

        $request->makePrevalidation();
        $request->refresh();
        $this->assertNull($request->prevalidation);
        $this->assertEquals(PrevalidationRequest::STATE_FAIL, $request->state);
        $this->assertEquals(IConnectPrefill::PREFILL_ERROR_CONNECTION_ERROR, $request->failed_reason);

        $this->deleteJson(
            "/api/v1/platform/organizations/$organization->id/prevalidation-requests/$request->id",
            headers: $this->makeApiHeaders($this->makeIdentityProxy($organization->identity)),
        )->assertSuccessful();

        $this->assertNull(PrevalidationRequest::find($request->id));
    }
}
