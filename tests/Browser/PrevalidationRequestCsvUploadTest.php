<?php

namespace Browser;

use App\Models\Fund;
use App\Models\FundCriterion;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\PersonBsnApiRecordType;
use App\Models\PrevalidationRequest;
use App\Models\RecordType;
use App\Services\IConnectApiService\IConnectPrefill;
use Facebook\WebDriver\Exception\TimeoutException;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\NavigatesFrontendDashboard;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFundRequestPrefills;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestPrevalidationRequests;
use Throwable;

class PrevalidationRequestCsvUploadTest extends DuskTestCase
{
    use WithFaker;
    use MakesTestFunds;
    use HasFrontendActions;
    use RollbackModelsTrait;
    use MakesTestFundRequests;
    use MakesTestOrganizations;
    use NavigatesFrontendDashboard;
    use MakesTestFundRequestPrefills;
    use MakesTestPrevalidationRequests;

    protected string $csvPath = 'public/prevalidation_request_batch_test.csv';

    /**
     * Test success upload and different results of process uploaded requests:
     *  - success (prevalidation created)
     *  - iConnect connection error
     *  - iConnect not found by bsn
     *  - not all required criteria filled with prefills
     * @throws Throwable
     */
    public function testPrevalidationRequestCsvUploadSuccessAndAfterProcess(): void
    {
        // configure implementation and organization for prefills
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $this->assertNotNull($implementation);
        $this->assertNotNull($organization);

        // get default data like record types, record keys, criteria (same for most of the tests)
        // here will be two main record types and record keys for prefill and manual: prefillKey and manualKey
        // criteria contains prefillKey, 'Partner count', 'Children count' and manualKey
        $defaultData = $this->prepareDefaultData($organization);

        $implementationData = $implementation->only(['digid_enabled', 'digid_required']);
        $organizationData = $organization->only([
            'fund_request_resolve_policy', 'bsn_enabled', 'iconnect_env', 'iconnect_key', 'iconnect_key_pass',
            'iconnect_cert', 'iconnect_cert_pass', 'iconnect_cert_trust', 'iconnect_target_binding',
            'iconnect_api_oin', 'iconnect_base_url', 'allow_prevalidation_requests',
        ]);

        $prefillRecordType = PersonBsnApiRecordType::create([
            'person_bsn_api_field' => 'naam.geslachtsnaam',
            'record_type_key' => $defaultData['prefillKey'],
        ]);

        $fund = $this->makeTestFund($organization, [
            'type' => 'budget',
        ], [
            'allow_fund_request_prefill' => true,
            'allow_prevalidations' => false,
            'key' => 'nijmegen-vi',
            'csv_primary_key' => 'uid',
        ], $implementation);

        $this->rollbackModels([
            [$implementation, $implementationData],
            [$organization, $organizationData],
        ], function () use ($implementation, $organization, $fund, $defaultData) {
            // configure iConnect settings and seed criteria
            $this->enablePersonBsnApiForOrganization($organization);
            $this->enablePrevalidationRequestForOrganization($organization);
            $this->makeFundCriteria($fund, $defaultData['criteria']);

            $this->browse(function (Browser $browser) use ($implementation, $fund, $defaultData) {
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

                $requestDataPrefillSuccess = [
                    'bsn' => '999993112',
                    'uid' => token_generator()->generate(32),
                    $defaultData['manualKey'] => 3,
                ];

                $requestDataPrefillFailConnectionError = [
                    'bsn' => '159786575',
                    'uid' => token_generator()->generate(32),
                    $defaultData['manualKey'] => 3,
                ];

                $requestDataPrefillFailNotFound = [
                    'bsn' => '159835562',
                    'uid' => token_generator()->generate(32),
                    $defaultData['manualKey'] => 3,
                ];

                $requestDataPrefillFailNotMetRequiredCriteria = [
                    'bsn' => '216506414',
                    'uid' => token_generator()->generate(32),
                    $defaultData['manualKey'] => 3,
                ];

                $browser->visit($implementation->urlSponsorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $implementation->organization->identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $implementation->organization->identity);
                $this->selectDashboardOrganization($browser, $implementation->organization);

                $this->goToPrevalidationRequestsPage($browser, $fund);

                $this->uploadPrevalidationRequestsBatch($browser, [
                    $requestDataPrefillSuccess,
                    $requestDataPrefillFailConnectionError,
                    $requestDataPrefillFailNotFound,
                    $requestDataPrefillFailNotMetRequiredCriteria,
                ]);

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

                // Logout
                $this->logout($browser);
            });
        }, function () use ($fund, $defaultData, $prefillRecordType) {
            $fund && $this->deleteFund($fund);
            $prefillRecordType?->delete();
            $defaultData['recordTypes']->each(fn (RecordType $recordType) => $recordType->delete());
        });
    }

    /**
     * Test frontend validation required BSN and criteria fields.
     * @throws Throwable
     */
    public function testPrevalidationRequestCsvUploadFailRequiredCriteria(): void
    {
        $this->fakePersonBsnApiResponses();

        // configure implementation and organization for prefills
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $this->assertNotNull($implementation);
        $this->assertNotNull($organization);

        // get default data like record types, record keys, criteria (same for most of the tests)
        // here will be two main record types and record keys for prefill and manual: prefillKey and manualKey
        // criteria contains prefillKey, 'Partner count', 'Children count' and manualKey
        $defaultData = $this->prepareDefaultData($organization);

        $implementationData = $implementation->only(['digid_enabled', 'digid_required']);
        $organizationData = $organization->only([
            'fund_request_resolve_policy', 'bsn_enabled', 'iconnect_env', 'iconnect_key', 'iconnect_key_pass',
            'iconnect_cert', 'iconnect_cert_pass', 'iconnect_cert_trust', 'iconnect_target_binding',
            'iconnect_api_oin', 'iconnect_base_url', 'allow_prevalidation_requests',
        ]);

        $prefillRecordType = PersonBsnApiRecordType::create([
            'person_bsn_api_field' => 'naam.geslachtsnaam',
            'record_type_key' => $defaultData['prefillKey'],
        ]);

        $fund = $this->makeTestFund($organization, [
            'type' => 'budget',
        ], [
            'allow_fund_request_prefill' => true,
            'allow_prevalidations' => false,
            'key' => 'nijmegen-vi',
            'csv_primary_key' => 'uid',
        ], $implementation);

        $this->rollbackModels([
            [$implementation, $implementationData],
            [$organization, $organizationData],
        ], function () use ($implementation, $organization, $fund, $defaultData) {
            // configure iConnect settings and seed criteria
            $this->enablePersonBsnApiForOrganization($organization);
            $this->enablePrevalidationRequestForOrganization($organization);
            $this->makeFundCriteria($fund, $defaultData['criteria']);

            $this->browse(function (Browser $browser) use ($implementation, $fund, $defaultData) {
                $browser->visit($implementation->urlSponsorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $implementation->organization->identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $implementation->organization->identity);
                $this->selectDashboardOrganization($browser, $implementation->organization);

                $this->goToPrevalidationRequestsPage($browser, $fund);

                // assert missed BSN
                $this->uploadPrevalidationRequestsBatch($browser, [[
                    'uid' => token_generator()->generate(32),
                    $defaultData['manualKey'] => 3,
                ]], assertSuccess: false);

                $this->assertValidationError($browser, 'BSN');
                $this->closeUploadModal($browser);

                // assert missed criteria
                $this->uploadPrevalidationRequestsBatch($browser, [[
                    'bsn' => '999993112',
                    'uid' => token_generator()->generate(32),
                ]], assertSuccess: false);

                $this->assertValidationError($browser, $defaultData['manualKey']);
                $this->closeUploadModal($browser);

                // assert wrong criteria value
                $this->uploadPrevalidationRequestsBatch($browser, [[
                    'bsn' => '999993112',
                    'uid' => token_generator()->generate(32),
                    $defaultData['manualKey'] => 'wrong',
                ]], assertSuccess: false);

                $this->submitUploadForm($browser);
                $this->assertDuplicateModalAndClose($browser);
                $this->closeUploadModal($browser);

                // Logout
                $this->logout($browser);
            });
        }, function () use ($fund, $defaultData, $prefillRecordType) {
            $fund && $this->deleteFund($fund);
            $prefillRecordType?->delete();
            $defaultData['recordTypes']->each(fn (RecordType $recordType) => $recordType->delete());
        });
    }

    /**
     * Test validation if try to upload BSN, that already exists.
     * @throws Throwable
     */
    public function testPrevalidationRequestCsvUploadValidationSameBsn(): void
    {
        $this->fakePersonBsnApiResponses();

        // configure implementation and organization for prefills
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $this->assertNotNull($implementation);
        $this->assertNotNull($organization);

        // get default data like record types, record keys, criteria (same for most of the tests)
        // here will be two main record types and record keys for prefill and manual: prefillKey and manualKey
        // criteria contains prefillKey, 'Partner count', 'Children count' and manualKey
        $defaultData = $this->prepareDefaultData($organization);

        $implementationData = $implementation->only(['digid_enabled', 'digid_required']);
        $organizationData = $organization->only([
            'fund_request_resolve_policy', 'bsn_enabled', 'iconnect_env', 'iconnect_key', 'iconnect_key_pass',
            'iconnect_cert', 'iconnect_cert_pass', 'iconnect_cert_trust', 'iconnect_target_binding',
            'iconnect_api_oin', 'iconnect_base_url', 'allow_prevalidation_requests',
        ]);

        $prefillRecordType = PersonBsnApiRecordType::create([
            'person_bsn_api_field' => 'naam.geslachtsnaam',
            'record_type_key' => $defaultData['prefillKey'],
        ]);

        $fund = $this->makeTestFund($organization, [
            'type' => 'budget',
        ], [
            'allow_fund_request_prefill' => true,
            'allow_prevalidations' => false,
            'key' => 'nijmegen-vi',
            'csv_primary_key' => 'uid',
        ], $implementation);

        $this->rollbackModels([
            [$implementation, $implementationData],
            [$organization, $organizationData],
        ], function () use ($implementation, $organization, $fund, $defaultData) {
            // configure iConnect settings and seed criteria
            $this->enablePersonBsnApiForOrganization($organization);
            $this->enablePrevalidationRequestForOrganization($organization);
            $this->makeFundCriteria($fund, $defaultData['criteria']);

            $this->browse(function (Browser $browser) use ($implementation, $fund, $defaultData) {
                $browser->visit($implementation->urlSponsorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $implementation->organization->identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $implementation->organization->identity);
                $this->selectDashboardOrganization($browser, $implementation->organization);

                $this->goToPrevalidationRequestsPage($browser, $fund);

                $requestData = [
                    'bsn' => '999993112',
                    'uid' => token_generator()->generate(32),
                    $defaultData['manualKey'] => 3,
                ];

                $this->uploadPrevalidationRequestsBatch($browser, [$requestData]);
                $this->assertPrevalidationRequestCreated($fund, $requestData);

                $this->uploadPrevalidationRequestsBatch($browser, [$requestData], assertSuccess: false);
                $this->submitUploadForm($browser);
                $this->assertDuplicateModalAndClose($browser);
                $this->closeUploadModal($browser);

                // Logout
                $this->logout($browser);
            });
        }, function () use ($fund, $defaultData, $prefillRecordType) {
            $fund && $this->deleteFund($fund);
            $prefillRecordType?->delete();
            $defaultData['recordTypes']->each(fn (RecordType $recordType) => $recordType->delete());
        });
    }

    /**
     * Test validation if there are optional criteria in required group (so at least one of criteria must present in row).
     * @throws Throwable
     */
    public function testPrevalidationRequestCsvUploadValidationCriteriaGroup(): void
    {
        $this->fakePersonBsnApiResponses();

        // configure implementation and organization for prefills
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $this->assertNotNull($implementation);
        $this->assertNotNull($organization);

        // create record types used by the full workflow
        $recordTypeConfigs = [
            ['key' => 'given_name', 'type' => RecordType::TYPE_STRING, 'control_type' => RecordType::CONTROL_TYPE_TEXT],
            ['key' => 'family_name', 'type' => RecordType::TYPE_STRING, 'control_type' => RecordType::CONTROL_TYPE_TEXT],
            ['key' => Fund::RECORD_TYPE_KEY_PARTNERS_SAME_ADDRESS, 'type' => RecordType::TYPE_NUMBER, 'control_type' => RecordType::CONTROL_TYPE_NUMBER],
            ['key' => Fund::RECORD_TYPE_KEY_CHILDREN_SAME_ADDRESS, 'type' => RecordType::TYPE_NUMBER, 'control_type' => RecordType::CONTROL_TYPE_NUMBER],
            ['key' => 'income_checkbox_paid_work', 'type' => RecordType::TYPE_BOOL, 'control_type' => RecordType::CONTROL_TYPE_CHECKBOX],
            ['key' => 'income_checkbox_subsidy', 'type' => RecordType::TYPE_BOOL, 'control_type' => RecordType::CONTROL_TYPE_CHECKBOX],
            ['key' => 'income_checkbox_wia', 'type' => RecordType::TYPE_BOOL, 'control_type' => RecordType::CONTROL_TYPE_CHECKBOX],
            ['key' => 'income_checkbox_alimony', 'type' => RecordType::TYPE_BOOL, 'control_type' => RecordType::CONTROL_TYPE_CHECKBOX],
            ['key' => 'income_checkbox_own_company', 'type' => RecordType::TYPE_BOOL, 'control_type' => RecordType::CONTROL_TYPE_CHECKBOX],
            ['key' => 'income_checkbox_hobby', 'type' => RecordType::TYPE_BOOL, 'control_type' => RecordType::CONTROL_TYPE_CHECKBOX],
            ['key' => 'income_checkbox_tax_credit', 'type' => RecordType::TYPE_BOOL, 'control_type' => RecordType::CONTROL_TYPE_CHECKBOX],
            ['key' => 'income_checkbox_other', 'type' => RecordType::TYPE_BOOL, 'control_type' => RecordType::CONTROL_TYPE_CHECKBOX],
        ];

        $recordTypes = collect($recordTypeConfigs)
            ->map(fn (array $config) => $this->makeRecordTypeForKey(
                $organization,
                $config['key'],
                $config['type'],
                $config['control_type'],
            ))
            ->filter(fn (RecordType $recordType) => $recordType->wasRecentlyCreated);

        // define person bsn prefill mappings
        $prefillRecordTypes = collect([
            PersonBsnApiRecordType::firstOrCreate([
                'person_bsn_api_field' => 'naam.voornamen',
                'record_type_key' => 'given_name',
            ]),
            PersonBsnApiRecordType::firstOrCreate([
                'person_bsn_api_field' => 'naam.geslachtsnaam',
                'record_type_key' => 'family_name',
            ]),
        ])->filter(fn (PersonBsnApiRecordType $recordType) => $recordType->wasRecentlyCreated);

        $fund = $this->makeTestFund($organization, [
            'type' => 'budget',
        ], [
            'allow_fund_request_prefill' => true,
            'allow_prevalidations' => false,
            'key' => 'nijmegen-vi',
            'csv_primary_key' => 'uid',
        ], $implementation);

        $incomeGroup = $this->makeCriteriaGroup(
            $fund,
            title: 'Enter how much income you and/or your partner have.',
            description: 'Vink aan welke inkomsten u en/of uw partner hebben gehad',
            required: true,
        );

        // define criteria with steps, groups, and rules
        $criteria = [[
            'title' => 'First name',
            'description' => '',
            'record_type_key' => 'given_name',
            'operator' => '*',
            'value' => '',
            'show_attachment' => false,
            'step' => 'Step 1: Personal information',
            'fill_type' => FundCriterion::FILL_TYPE_PREFILL,
        ], [
            'title' => 'Last name',
            'description' => '',
            'record_type_key' => 'family_name',
            'operator' => '*',
            'value' => '',
            'show_attachment' => false,
            'step' => 'Step 1: Personal information',
            'fill_type' => FundCriterion::FILL_TYPE_PREFILL,
        ], [
            'title' => 'Partner',
            'description' => '',
            'record_type_key' => 'partner_same_address_nth',
            'operator' => '*',
            'value' => '',
            'show_attachment' => false,
            'step' => 'Step 2: Family situation',
            'fill_type' => FundCriterion::FILL_TYPE_PREFILL,
            'optional' => true,
        ], [
            'title' => 'Children',
            'description' => '',
            'record_type_key' => 'children_same_address_nth',
            'operator' => '*',
            'value' => '',
            'show_attachment' => false,
            'step' => 'Step 2: Family situation',
            'fill_type' => FundCriterion::FILL_TYPE_PREFILL,
            'optional' => true,
        ], [
            'title' => 'Paid work (Wages)',
            'label' => 'Paid work (Wages)',
            'description' => '',
            'record_type_key' => 'income_checkbox_paid_work',
            'operator' => '*',
            'value' => 'Ja',
            'show_attachment' => true,
            'optional' => true,
            'step' => 'Step 3: Income',
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
            'step' => 'Step 3: Income',
            'fund_criteria_group_id' => $incomeGroup->id,
        ], [
            'title' => 'WIA benefit',
            'label' => 'WIA benefit',
            'description' => '',
            'record_type_key' => 'income_checkbox_wia',
            'operator' => '*',
            'value' => 'Ja',
            'show_attachment' => true,
            'optional' => true,
            'step' => 'Step 3: Income',
            'fund_criteria_group_id' => $incomeGroup->id,
        ], [
            'title' => 'Alimentatie',
            'label' => 'Alimentatie',
            'description' => '',
            'record_type_key' => 'income_checkbox_alimony',
            'operator' => '*',
            'value' => 'Ja',
            'show_attachment' => true,
            'optional' => true,
            'step' => 'Step 3: Income',
            'fund_criteria_group_id' => $incomeGroup->id,
        ], [
            'title' => 'Eigen bedrijf',
            'label' => 'Eigen bedrijf',
            'description' => '',
            'record_type_key' => 'income_checkbox_own_company',
            'operator' => '*',
            'value' => 'Ja',
            'show_attachment' => true,
            'optional' => true,
            'step' => 'Step 3: Income',
            'fund_criteria_group_id' => $incomeGroup->id,
        ], [
            'title' => 'Hobby',
            'label' => 'Hobby',
            'description' => '',
            'record_type_key' => 'income_checkbox_hobby',
            'operator' => '*',
            'value' => 'Ja',
            'show_attachment' => true,
            'optional' => true,
            'step' => 'Step 3: Income',
            'fund_criteria_group_id' => $incomeGroup->id,
        ], [
            'title' => 'Heffingskorting',
            'label' => 'Heffingskorting',
            'description' => '',
            'record_type_key' => 'income_checkbox_tax_credit',
            'operator' => '*',
            'value' => 'Ja',
            'show_attachment' => true,
            'optional' => true,
            'step' => 'Step 3: Income',
            'fund_criteria_group_id' => $incomeGroup->id,
        ], [
            'title' => 'Ander inkomen, namelijk:',
            'label' => 'Ander inkomen, namelijk:',
            'description' => '',
            'record_type_key' => 'income_checkbox_other',
            'operator' => '*',
            'value' => 'Ja',
            'show_attachment' => true,
            'optional' => true,
            'step' => 'Step 3: Income',
            'fund_criteria_group_id' => $incomeGroup->id,
        ]];

        $implementationData = $implementation->only(['digid_enabled', 'digid_required']);
        $organizationData = $organization->only([
            'fund_request_resolve_policy', 'bsn_enabled', 'iconnect_env', 'iconnect_key', 'iconnect_key_pass',
            'iconnect_cert', 'iconnect_cert_pass', 'iconnect_cert_trust', 'iconnect_target_binding',
            'iconnect_api_oin', 'iconnect_base_url', 'allow_prevalidation_requests',
        ]);

        $this->rollbackModels([
            [$implementation, $implementationData],
            [$organization, $organizationData],
        ], function () use ($implementation, $organization, $fund, $criteria) {
            // configure iConnect settings and seed criteria
            $this->enablePersonBsnApiForOrganization($organization);
            $this->enablePrevalidationRequestForOrganization($organization);
            $this->makeFundCriteria($fund, $criteria);

            $this->browse(function (Browser $browser) use ($implementation, $fund) {
                $browser->visit($implementation->urlSponsorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $implementation->organization->identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $implementation->organization->identity);
                $this->selectDashboardOrganization($browser, $implementation->organization);

                $this->goToPrevalidationRequestsPage($browser, $fund);

                $requestData = [
                    'bsn' => '999993112',
                    'uid' => token_generator()->generate(32),
                ];

                $this->uploadPrevalidationRequestsBatch($browser, [$requestData], assertSuccess: false);
                $this->submitUploadForm($browser);
                $this->assertDuplicateModalAndClose($browser);
                $this->closeUploadModal($browser);

                // Logout
                $this->logout($browser);
            });
        }, function () use ($fund, $recordTypes, $prefillRecordTypes) {
            $fund && $this->deleteFund($fund);
            $prefillRecordTypes->each(fn (PersonBsnApiRecordType $recordType) => $recordType->delete());
            $recordTypes->each(fn (RecordType $recordType) => $recordType->delete());
        });
    }

    /**
     * @throws Throwable
     */
    public function testPrevalidationRequestResubmit(): void
    {
        // configure implementation and organization for prefills
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $this->assertNotNull($implementation);
        $this->assertNotNull($organization);

        // get default data like record types, record keys, criteria (same for most of the tests)
        // here will be two main record types and record keys for prefill and manual: prefillKey and manualKey
        // criteria contains prefillKey, 'Partner count', 'Children count' and manualKey
        $defaultData = $this->prepareDefaultData($organization);

        $implementationData = $implementation->only(['digid_enabled', 'digid_required']);
        $organizationData = $organization->only([
            'fund_request_resolve_policy', 'bsn_enabled', 'iconnect_env', 'iconnect_key', 'iconnect_key_pass',
            'iconnect_cert', 'iconnect_cert_pass', 'iconnect_cert_trust', 'iconnect_target_binding',
            'iconnect_api_oin', 'iconnect_base_url', 'allow_prevalidation_requests',
        ]);

        $prefillRecordType = PersonBsnApiRecordType::create([
            'person_bsn_api_field' => 'naam.geslachtsnaam',
            'record_type_key' => $defaultData['prefillKey'],
        ]);

        $fund = $this->makeTestFund($organization, [
            'type' => 'budget',
        ], [
            'allow_fund_request_prefill' => true,
            'allow_prevalidations' => false,
            'key' => 'nijmegen-vi',
            'csv_primary_key' => 'uid',
        ], $implementation);

        $this->rollbackModels([
            [$implementation, $implementationData],
            [$organization, $organizationData],
        ], function () use ($implementation, $organization, $fund, $defaultData) {
            // configure iConnect settings and seed criteria
            $this->enablePersonBsnApiForOrganization($organization);
            $this->enablePrevalidationRequestForOrganization($organization);
            $this->makeFundCriteria($fund, $defaultData['criteria']);

            $this->browse(function (Browser $browser) use ($implementation, $fund, $defaultData) {
                $this->fakePersonBsnApiResponses([
                    '999993112' => [
                        'status' => 500,
                        'body' => [],
                    ],
                ]);

                $requestDataPrefillFailConnectionError = [
                    'bsn' => '999993112',
                    'uid' => token_generator()->generate(32),
                    $defaultData['manualKey'] => 3,
                ];

                $browser->visit($implementation->urlSponsorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $implementation->organization->identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $implementation->organization->identity);
                $this->selectDashboardOrganization($browser, $implementation->organization);

                $this->goToPrevalidationRequestsPage($browser, $fund);
                $this->uploadPrevalidationRequestsBatch($browser, [$requestDataPrefillFailConnectionError]);
                $this->assertAndCloseSuccessNotification($browser);

                // assert if IConnect gives connection error - request got failed and right reason was stored in logs
                $request = $this->assertPrevalidationRequestCreated($fund, $requestDataPrefillFailConnectionError);
                $request->makePrevalidation();
                $this->assertNull($request->prevalidation);
                $this->assertEquals(PrevalidationRequest::STATE_FAIL, $request->state);
                $this->assertEquals(IConnectPrefill::PREFILL_ERROR_CONNECTION_ERROR, $request->failed_reason);

                // find request in list and resubmit it
                $browser->refresh();
                $this->searchTable($browser, '@tablePrevalidationRequest', $request->bsn, $request->id);

                $browser->waitFor("@tablePrevalidationRequestRow$request->id");
                $browser->within("@tablePrevalidationRequestRow$request->id", fn (Browser $b) => $b->press('@btnPrevalidationRequestMenu'));

                $browser->waitFor("@btnPrevalidationRequestResubmit$request->id");
                $browser->press("@btnPrevalidationRequestResubmit$request->id");

                $this->assertAndCloseSuccessNotification($browser);

                // assert that state now is pending
                $request->refresh();
                $this->assertEquals(PrevalidationRequest::STATE_PENDING, $request->state);

                // reload browser and assert that menu for request is missing
                $browser->refresh();
                $this->searchTable($browser, '@tablePrevalidationRequest', $request->bsn, $request->id);
                $browser->waitFor("@tablePrevalidationRequestRow$request->id");
                $browser->within("@tablePrevalidationRequestRow$request->id", fn (Browser $b) => $b->assertMissing('@btnPrevalidationRequestMenu'));

                // Logout
                $this->logout($browser);
            });
        }, function () use ($fund, $defaultData, $prefillRecordType) {
            $fund && $this->deleteFund($fund);
            $prefillRecordType?->delete();
            $defaultData['recordTypes']->each(fn (RecordType $recordType) => $recordType->delete());
        });
    }

    /**
     * @throws Throwable
     */
    public function testPrevalidationRequestDelete(): void
    {
        // configure implementation and organization for prefills
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $this->assertNotNull($implementation);
        $this->assertNotNull($organization);

        // get default data like record types, record keys, criteria (same for most of the tests)
        // here will be two main record types and record keys for prefill and manual: prefillKey and manualKey
        // criteria contains prefillKey, 'Partner count', 'Children count' and manualKey
        $defaultData = $this->prepareDefaultData($organization);

        $implementationData = $implementation->only(['digid_enabled', 'digid_required']);
        $organizationData = $organization->only([
            'fund_request_resolve_policy', 'bsn_enabled', 'iconnect_env', 'iconnect_key', 'iconnect_key_pass',
            'iconnect_cert', 'iconnect_cert_pass', 'iconnect_cert_trust', 'iconnect_target_binding',
            'iconnect_api_oin', 'iconnect_base_url', 'allow_prevalidation_requests',
        ]);

        $prefillRecordType = PersonBsnApiRecordType::create([
            'person_bsn_api_field' => 'naam.geslachtsnaam',
            'record_type_key' => $defaultData['prefillKey'],
        ]);

        $fund = $this->makeTestFund($organization, [
            'type' => 'budget',
        ], [
            'allow_fund_request_prefill' => true,
            'allow_prevalidations' => false,
            'key' => 'nijmegen-vi',
            'csv_primary_key' => 'uid',
        ], $implementation);

        $this->rollbackModels([
            [$implementation, $implementationData],
            [$organization, $organizationData],
        ], function () use ($implementation, $organization, $fund, $defaultData) {
            // configure iConnect settings and seed criteria
            $this->enablePersonBsnApiForOrganization($organization);
            $this->enablePrevalidationRequestForOrganization($organization);
            $this->makeFundCriteria($fund, $defaultData['criteria']);

            $this->browse(function (Browser $browser) use ($implementation, $fund, $defaultData) {
                $this->fakePersonBsnApiResponses([
                    '999993112' => [
                        'status' => 500,
                        'body' => [],
                    ],
                ]);

                $requestDataPrefillFailConnectionError = [
                    'bsn' => '999993112',
                    'uid' => token_generator()->generate(32),
                    $defaultData['manualKey'] => 3,
                ];

                $browser->visit($implementation->urlSponsorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $implementation->organization->identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $implementation->organization->identity);
                $this->selectDashboardOrganization($browser, $implementation->organization);

                $this->goToPrevalidationRequestsPage($browser, $fund);
                $this->uploadPrevalidationRequestsBatch($browser, [$requestDataPrefillFailConnectionError]);
                $this->assertAndCloseSuccessNotification($browser);

                // assert if IConnect gives connection error - request got failed and right reason was stored in logs
                $request = $this->assertPrevalidationRequestCreated($fund, $requestDataPrefillFailConnectionError);
                $request->makePrevalidation();
                $this->assertNull($request->prevalidation);
                $this->assertEquals(PrevalidationRequest::STATE_FAIL, $request->state);
                $this->assertEquals(IConnectPrefill::PREFILL_ERROR_CONNECTION_ERROR, $request->failed_reason);

                // find request in list and delete it
                $browser->refresh();
                $this->searchTable($browser, '@tablePrevalidationRequest', $request->bsn, $request->id);

                $browser->waitFor("@tablePrevalidationRequestRow$request->id");
                $browser->within("@tablePrevalidationRequestRow$request->id", fn (Browser $b) => $b->press('@btnPrevalidationRequestMenu'));

                $browser->waitFor("@btnPrevalidationRequestDelete$request->id");
                $browser->press("@btnPrevalidationRequestDelete$request->id");

                $this->assertAndCloseSuccessNotification($browser);

                // assert that request is missing
                $this->searchTable($browser, '@tablePrevalidationRequest', $request->bsn, null, 0);

                // Logout
                $this->logout($browser);
            });
        }, function () use ($fund, $defaultData, $prefillRecordType) {
            $fund && $this->deleteFund($fund);
            $prefillRecordType?->delete();
            $defaultData['recordTypes']->each(fn (RecordType $recordType) => $recordType->delete());
        });
    }

    /**
     * @param Browser $browser
     * @param string $error
     * @throws TimeoutException
     * @throws \Facebook\WebDriver\Exception\ElementClickInterceptedException
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @return void
     */
    protected function assertValidationError(Browser $browser, string $error): void
    {
        $browser->waitFor('.csv-file-error');
        $browser->assertSeeIn('.csv-file-error', $error);
        $browser->assertDisabled('@uploadFileButton');
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @throws \Facebook\WebDriver\Exception\ElementClickInterceptedException
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @return void
     */
    protected function closeUploadModal(Browser $browser): void
    {
        $browser->click('@closeModalButton');
        $browser->waitUntilMissing('@modalPrevalidationRequestUpload');
    }

    /**
     * @param Organization $organization
     * @return array
     */
    protected function prepareDefaultData(Organization $organization): array
    {
        $prefillKey = token_generator()->generate(16);
        $manualKey = token_generator()->generate(16);

        $recordTypes = collect([
            $this->makeRecordTypeForKey(
                $organization,
                $prefillKey,
                RecordType::TYPE_STRING,
                RecordType::CONTROL_TYPE_TEXT,
            ),
            $this->makeRecordTypeForKey(
                $organization,
                $manualKey,
                RecordType::TYPE_NUMBER,
                RecordType::CONTROL_TYPE_NUMBER,
            ),
            $this->makeRecordTypeForKey(
                $organization,
                Fund::RECORD_TYPE_KEY_PARTNERS_SAME_ADDRESS,
                RecordType::TYPE_NUMBER,
                RecordType::CONTROL_TYPE_NUMBER,
            ),
            $this->makeRecordTypeForKey(
                $organization,
                Fund::RECORD_TYPE_KEY_CHILDREN_SAME_ADDRESS,
                RecordType::TYPE_NUMBER,
                RecordType::CONTROL_TYPE_NUMBER,
            ),
        ])->filter(fn (RecordType $recordType) => $recordType->wasRecentlyCreated);

        $criteria = [[
            'step' => 'Step #1',
            'title' => 'Prefill last name',
            'value' => 'any',
            'operator' => '*',
            'optional' => false,
            'record_type_key' => $prefillKey,
            'show_attachment' => false,
            'fill_type' => FundCriterion::FILL_TYPE_PREFILL,
        ], [
            'step' => 'Step #1',
            'title' => 'Partner count',
            'value' => 1,
            'operator' => '>=',
            'optional' => true,
            'record_type_key' => Fund::RECORD_TYPE_KEY_PARTNERS_SAME_ADDRESS,
            'show_attachment' => false,
            'fill_type' => FundCriterion::FILL_TYPE_PREFILL,
        ], [
            'step' => 'Step #1',
            'title' => 'Children count',
            'value' => 1,
            'operator' => '>=',
            'optional' => true,
            'record_type_key' => Fund::RECORD_TYPE_KEY_CHILDREN_SAME_ADDRESS,
            'show_attachment' => false,
            'fill_type' => FundCriterion::FILL_TYPE_PREFILL,
        ], [
            'step' => 'Step #1',
            'title' => 'Manual number',
            'value' => 1,
            'operator' => '>=',
            'optional' => false,
            'record_type_key' => $manualKey,
            'show_attachment' => false,
        ]];

        return compact('recordTypes', 'prefillKey', 'manualKey', 'criteria');
    }

    /**
     * @param Browser $browser
     * @param array $prevalidationsData
     * @param bool $assertSuccess
     * @throws TimeoutException
     * @return void
     */
    protected function uploadPrevalidationRequestsBatch(
        Browser $browser,
        array $prevalidationsData,
        bool $assertSuccess = true,
    ): void {
        $browser->waitFor('@uploadPrevalidationRequestsBatchButton');
        $browser->element('@uploadPrevalidationRequestsBatchButton')->click();

        $browser->waitFor('@modalFundSelectSubmit');
        $browser->element('@modalFundSelectSubmit')->click();

        $browser->waitFor('@modalPrevalidationRequestUpload');

        $browser->waitFor('@selectFileButton');
        $browser->element('@selectFileButton')->click();

        $this->createFile($prevalidationsData);
        $browser->attach('@inputUpload', Storage::path($this->csvPath));

        if ($assertSuccess) {
            $this->submitUploadForm($browser);
            $browser->waitFor('@successUploadIcon');

            $browser->element('@closeModalButton')->click();
            $browser->waitUntilMissing('@modalPrevalidationUpload');
        }

        Storage::delete($this->csvPath);
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return void
     */
    protected function submitUploadForm(Browser $browser): void
    {
        $browser->waitFor('@uploadFileButton');
        $browser->element('@uploadFileButton')->click();
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return void
     */
    protected function assertDuplicateModalAndClose(Browser $browser): void
    {
        $browser->waitFor('@modalDuplicatesPicker');

        $browser->waitFor('@modalDuplicatesPickerConfirm');
        $browser->element('@modalDuplicatesPickerConfirm')->click();

        $browser->waitUntilMissing('@modalDuplicatesPicker');
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
}
