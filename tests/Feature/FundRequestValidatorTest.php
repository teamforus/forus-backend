<?php

namespace Tests\Feature;

use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\FundRequestRecordGroup;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\RecordType;
use App\Models\Role;
use App\Services\MediaService\Traits\UsesMediaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestIdentities;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class FundRequestValidatorTest extends TestCase
{
    use WithFaker;
    use MakesTestFunds;
    use UsesMediaService;
    use MakesTestIdentities;
    use DatabaseTransactions;
    use MakesTestFundRequests;
    use MakesTestOrganizations;

    /**
     * @var array|array[]
     */
    protected array $fundRequestStructure = [
        'data' => [
            'id', 'state', 'fund_id', 'note', 'lead_time_days', 'lead_time_locale',
            'contact_information', 'state_locale', 'employee_id',
            'bsn', 'fund', 'email', 'replaced', 'employee',
        ],

        'data.records.*' => [
            'id', 'record_type_key', 'fund_request_id', 'fund_criterion_id',
            'value', 'files', 'history', 'clarifications',
            'record_type' => [
                'name', 'key', 'type', 'options',
            ],
        ],
        'data.allowed_employees.*' => [
            'id', 'organization_id', 'identity_address', 'email',
        ],
    ];

    /**
     * Test the approval of a fund request with a predefined amount preset.
     *
     * @return void
     */
    public function testFundRequestApproveWithAmountPresets()
    {
        $fund1 = $this->setupNewFundAndCriteria();
        $fund2 = $this->makeTestFund($fund1->organization);

        $employee = $fund1->organization->findEmployee($fund1->organization->identity);
        $fundRequest = $this->makeIdentityAndFundRequest($fund1);

        $fund1->organization->forceFill([
            'allow_payouts' => true,
            'fund_request_resolve_policy' => Organization::FUND_REQUEST_POLICY_AUTO_REQUESTED,
        ])->save();

        $preset1 = $fund1->amount_presets()->create(['name' => 'PRESET #1', 'amount' => 100]);
        $preset3 = $fund2->amount_presets()->create(['name' => 'PRESET #2', 'amount' => 100]);

        $fundRequest->assignEmployee($employee);

        // assert validation error when employees are now allowed to use presets
        $fund1->updateFundsConfig(['allow_preset_amounts_validator' => false]);
        $this->apiFundRequestApproveRequest($fundRequest, $employee, ['fund_amount_preset_id' => $preset1->id])
            ->assertJsonValidationErrors(['fund_amount_preset_id']);

        // assert validation fund_amount_preset_id from another fund is used
        $fund1->updateFundsConfig(['allow_preset_amounts_validator' => true]);
        $fund2->updateFundsConfig(['allow_preset_amounts_validator' => true]);

        $this->apiFundRequestApproveRequest($fundRequest, $employee, ['fund_amount_preset_id' => $preset3->id])
            ->assertJsonValidationErrors(['fund_amount_preset_id']);

        // assert success when the correct fund_amount_preset_id is used for a fund where employees can use presets
        $fund1->updateFundsConfig(['allow_preset_amounts_validator' => true]);
        $this->apiFundRequestApproveRequest($fundRequest, $employee, ['fund_amount_preset_id' => $preset1->id])
            ->assertSuccessful();

        $this->assertEquals(FundRequest::STATE_APPROVED, $fundRequest->fresh()->state);
        $this->assertEquals($preset1->amount, $fundRequest->vouchers()->first()->amount);
    }

    /**
     * Test the approval of a fund request with a custom amount.
     *
     * @return void
     */
    public function testFundRequestApproveWithCustomAmount()
    {
        $fund = $this->setupNewFundAndCriteria();
        $fundRequest = $this->makeIdentityAndFundRequest($fund);
        $employee = $fund->organization->findEmployee($fund->organization->identity);

        $fund->organization->forceFill([
            'allow_payouts' => true,
            'fund_request_resolve_policy' => Organization::FUND_REQUEST_POLICY_AUTO_REQUESTED,
        ])->save();

        // restrict custom amount between 100 and 200 and assign the employee
        $fund->updateFundsConfig(['custom_amount_min' => 100, 'custom_amount_max' => 200]);
        $fundRequest->assignEmployee($employee);

        // assert error when custom amounts are not allowed.
        $fund->updateFundsConfig(['allow_custom_amounts_validator' => false]);
        $this->apiFundRequestApproveRequest($fundRequest, $employee, ['amount' => 150])->assertJsonValidationErrors(['amount']);

        //  assert error when the custom amount is outside the specified range.
        $fund->updateFundsConfig(['allow_custom_amounts_validator' => true]);
        $this->apiFundRequestApproveRequest($fundRequest, $employee, ['amount' => 300])->assertJsonValidationErrors(['amount']);

        // assert success approval with a valid custom amount.
        $this->apiFundRequestApproveRequest($fundRequest, $employee, ['amount' => 150])->assertSuccessful();

        $this->assertEquals(FundRequest::STATE_APPROVED, $fundRequest->fresh()->state);
        $this->assertEquals(150, $fundRequest->vouchers()->first()->amount);
    }

    /**
     * Tests the assignment of a partner BSN to a fund request.
     *
     * @return void
     */
    public function testFundRequestAssignPartnerBsn()
    {
        $fund = $this->setupNewFundAndCriteria();
        $fundRequest = $this->makeIdentityAndFundRequest($fund);

        $bsnData = ['value' => '123456782', 'record_type_key' => 'partner_bsn'];
        $employee = $fund->organization->findEmployee($fund->organization->identity);

        $fundRequest->assignEmployee($employee);

        // assert validation error for record_type_key if bsn_enabled is false
        $fund->organization->update(['bsn_enabled' => false]);
        $this->apiMakeFundRequestRecordRequest($fundRequest, $bsnData, $employee)->assertJsonValidationErrors(['record_type_key']);

        // assert success if bsn_enabled is true
        $fund->organization->update(['bsn_enabled' => true]);
        $this->apiMakeFundRequestRecordRequest($fundRequest, $bsnData, $employee)->assertSuccessful();

        // assert partner bsn record is updated and applied after approval
        $this->assertEquals($bsnData['value'], $fundRequest->records->firstWhere('record_type_key', 'partner_bsn')?->value);
        $this->apiFundRequestApproveRequest($fundRequest, $employee)->assertSuccessful();
        $this->assertSame($bsnData['value'], $fund->getTrustedRecordOfType($fundRequest->identity, 'partner_bsn')->value);
    }

    /**
     * Check that fund-request can be accepted, refused or dismissed.
     *
     * @throws Throwable
     */
    public function testFundRequestResolving()
    {
        $fund = $this->setupNewFundAndCriteria();
        $fundRequest = $this->makeIdentityAndFundRequest($fund);
        $employee = $fund->organization->findEmployee($fund->organization->identity);

        $fundRequest->assignEmployee($employee);

        DB::beginTransaction();
        $this->apiFundRequestApproveRequest($fundRequest, $employee)->assertSuccessful();
        DB::rollBack();

        DB::beginTransaction();
        $this->apiFundRequestDisregardRequest($fundRequest, ['notify' => false], $employee)->assertSuccessful();
        $this->apiGetOrganizationEmailLogsRequest($fund->organization, ['fund_request_id' => $fundRequest->id])
            ->assertSuccessful()
            ->assertJsonPath('data', fn ($list) => count($list) == 1)
            ->assertJsonPath('data', fn ($list) => collect($list)->where('type', 'fund_request_disregarded')->count() == 0);
        DB::rollBack();

        DB::beginTransaction();
        $this->apiFundRequestDisregardRequest($fundRequest, ['notify' => true], $employee)->assertSuccessful();
        $this->apiGetOrganizationEmailLogsRequest($fund->organization, ['fund_request_id' => $fundRequest->id])
            ->assertSuccessful()
            ->assertJsonPath('data', fn ($list) => count($list) == 2)
            ->assertJsonPath('data', fn ($list) => collect($list)->where('type', 'fund_request_disregarded')->count() == 1);
        DB::rollBack();

        DB::beginTransaction();
        $this->apiFundRequestDeclineRequest($fundRequest, [], $employee)->assertSuccessful();
        DB::rollBack();
    }

    /**
     * Tests the access permissions for employees based on their role and organization affiliation.
     *
     * @return void
     */
    public function testFundRequestAccessibleByEmployees()
    {
        $fund = $this->setupNewFundAndCriteria();
        $fundRequest = $this->makeIdentityAndFundRequest($fund);
        $otherOrganization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $validatorRoles = Role::whereRelation('permissions', 'key', Permission::VALIDATE_RECORDS)->pluck('id')->toArray();

        $employee1 = $fund->organization->findEmployee($fund->organization->identity);
        $employee2 = $fund->organization->addEmployee($this->makeIdentity($this->makeUniqueEmail()), $validatorRoles);
        $employee3 = $fund->organization->addEmployee($this->makeIdentity($this->makeUniqueEmail()));
        $employee4 = $otherOrganization->addEmployee($this->makeIdentity($this->makeUniqueEmail()), $validatorRoles);

        // assert access for organization's owner
        $this->apiGetFundRequestRequest($fund->organization, $employee1, $fundRequest)->assertSuccessful();
        $this->apiGetFundRequestsRequest($fund->organization, $employee1, [])
            ->assertSuccessful()
            ->assertJsonPath('data.*.id', fn ($list) => in_array($fundRequest->id, $list));

        // assert access for organization employee with all validate_records permission
        $this->apiGetFundRequestRequest($fund->organization, $employee2, $fundRequest)->assertSuccessful();
        $this->apiGetFundRequestsRequest($fund->organization, $employee2, [])
            ->assertSuccessful()
            ->assertJsonPath('data.*.id', fn ($list) => in_array($fundRequest->id, $list));

        // assert forbidden for organization employee without permissions
        $this->apiGetFundRequestRequest($fund->organization, $employee3, $fundRequest)->assertForbidden();
        $this->apiGetFundRequestsRequest($fund->organization, $employee3, [])
            ->assertForbidden();

        // assert forbidden for employee with correct permissions but from another organization
        $this->apiGetFundRequestRequest($otherOrganization, $employee4, $fundRequest)->assertForbidden();
        $this->apiGetFundRequestsRequest($fund->organization, $employee4, [])
            ->assertForbidden();

        // assert not visible for employee with correct permissions but for another organization
        $this->apiGetFundRequestRequest($otherOrganization, $employee4, $fundRequest)->assertForbidden();
        $this->apiGetFundRequestsRequest($otherOrganization, $employee4, [])
            ->assertSuccessful()
            ->assertJsonPath('data.*.id', fn ($list) => !in_array($fundRequest->id, $list));
    }

    /**
     * Tests the functionality of employees assigning and resigning fund requests.
     *
     * @return void
     */
    public function testEmployeeSelfAssigningFundRequests()
    {
        $fund = $this->setupNewFundAndCriteria();
        $fundRequest = $this->makeIdentityAndFundRequest($fund);
        $validatorRoles = Role::whereRelation('permissions', 'key', Permission::VALIDATE_RECORDS)->pluck('id')->toArray();

        $employeeWithPermissions = $fund->organization->addEmployee($this->makeIdentity($this->makeUniqueEmail()), $validatorRoles);
        $employeeWithoutPermissions = $fund->organization->addEmployee($this->makeIdentity($this->makeUniqueEmail()));

        // assert employee without the necessary permissions cannot assign a fund request to themselves
        $this->apiFundRequestAssignRequest($fundRequest, $employeeWithoutPermissions)->assertForbidden();

        // assert employee with the necessary permissions can successfully assign a fund request to themselves
        $this->apiFundRequestAssignRequest($fundRequest, $employeeWithPermissions)
            ->assertJsonPath('data.employee_id', $employeeWithPermissions->id)
            ->assertSuccessful();

        // assert employee cannot reassign a fund request they have already assigned
        $this->apiFundRequestAssignRequest($fundRequest, $employeeWithPermissions)->assertForbidden();

        // assert employee can successfully resign from a fund request they have assigned
        $this->apiFundRequestResignRequest($fundRequest, $employeeWithPermissions)
            ->assertJsonPath('data.employee_id', null)
            ->assertSuccessful();

        // assert employee cannot resign from a fund request if nobody is currently assigned
        $this->apiFundRequestResignRequest($fundRequest, $employeeWithPermissions)->assertForbidden();
    }

    /**
     * Tests the functionality of assigning an employee to a fund request by a supervisor.
     *
     * @return void
     */
    public function testFundRequestAssignEmployeeBySupervisor()
    {
        $fund = $this->setupNewFundAndCriteria();
        $fundRequest = $this->makeIdentityAndFundRequest($fund);

        $rolesManager = Role::where('key', 'supervisor_validator');
        $rolesValidator = Role::where('key', 'validation');
        $rolesNonValidator = Role::whereDoesntHaveRelation('permissions', 'key', Permission::VALIDATE_RECORDS);

        $employeeManager = $fund->organization->addEmployee($this->makeIdentity(), $rolesManager->pluck('id')->toArray());
        $employeeValidator = $fund->organization->addEmployee($this->makeIdentity(), $rolesValidator->pluck('id')->toArray());
        $employeeNonValidator = $fund->organization->addEmployee($this->makeIdentity(), $rolesNonValidator->pluck('id')->toArray());

        // assert assign an employee with incorrect permissions should return a forbidden response.
        $this->apiFundRequestAssignEmployeeRequest($fundRequest, $employeeValidator, ['employee_id' => $employeeValidator->id])
            ->assertForbidden();

        // assert assigning an employee without correct permissions should result in validation errors.
        $this->apiFundRequestAssignEmployeeRequest($fundRequest, $employeeManager, ['employee_id' => $employeeNonValidator->id])
            ->assertJsonValidationErrors(['employee_id']);

        // assert successfully assigning an employee with correct permissions should update the fund request's employee ID.
        $this->apiFundRequestAssignEmployeeRequest($fundRequest, $employeeManager, ['employee_id' => $employeeValidator->id])
            ->assertJsonPath('data.employee_id', $employeeValidator->id)
            ->assertSuccessful();

        // assert is not possible to reassign an already assigned employee
        $this->apiFundRequestAssignEmployeeRequest($fundRequest, $employeeManager, ['employee_id' => $employeeValidator->id])
            ->assertJsonValidationErrors(['employee_id']);

        // assert resigning an employee from the fund request should clear the employee ID.
        $this->apiFundRequestResignEmployeeRequest($fundRequest, $employeeManager, ['employee_id' => $employeeValidator->id])
            ->assertJsonPath('data.employee_id', null)
            ->assertSuccessful();
    }

    /**
     * Test the functionality of answering a fund request clarification.
     *
     * @return void
     */
    public function testFundRequestClarificationAnswer()
    {
        $fund = $this->setupNewFundAndCriteria();
        $fundRequest = $this->makeIdentityAndFundRequest($fund);
        $employee = $fund->organization->findEmployee($fund->organization->identity);

        $questionData = [
            'question' => $this->faker()->text(),
            'text_requirement' => 'required',
            'files_requirement' => 'required',
            'fund_request_record_id' => $fundRequest->records[0]->id,
        ];

        $answerData = ['answer' => $this->faker()->text()];
        $answerFileData = ['file' => UploadedFile::fake()->image('doc.jpg'), 'type' => 'fund_request_clarification_proof'];

        // assert clarification request can be requested for a fund request
        $this->apiMakeFundRequestClarificationRequest($fundRequest, $employee, $questionData)
            ->assertSuccessful()
            ->assertJsonPath('data.question', $questionData['question']);

        // upload files for clarification request
        $answerData['files'] = (array) $this->apiUploadFileRequest($fundRequest->identity, $answerFileData)
            ->assertSuccessful()
            ->json('data.uid');

        // assert requester can answer the clarification request
        $this->apiRespondFundRequestClarificationRequest($fundRequest->clarifications[0], $fundRequest->identity, $answerData)
            ->assertSuccessful()
            ->assertJsonPath('data.answer', $answerData['answer'])
            ->assertJsonPath('data.fund_request_record_id', $fundRequest->records[0]->id)
            ->assertJsonPath('data.state', $fundRequest->clarifications[0]::STATE_ANSWERED);

        // assert answer received by the validator
        $this->apiGetFundRequestRequest($fund->organization, $employee, $fundRequest)
            ->assertSuccessful()
            ->assertJsonPath('data.records.0.clarifications', fn ($clarifications) => count($clarifications) === 1)
            ->assertJsonPath('data.records.0.clarifications.0.state', $fundRequest->clarifications[0]::STATE_ANSWERED)
            ->assertJsonPath('data.records.0.clarifications.0.answer', $answerData['answer'])
            ->assertJsonPath('data.records.0.clarifications.0.question', $questionData['question']);
    }

    /**
     * Tests the visibility of fund requests based on different state groups.
     *
     * @return void
     */
    public function testFundRequestFilterByStateGroup()
    {
        $fund = $this->setupNewFundAndCriteria();
        $fundRequest = $this->makeIdentityAndFundRequest($fund);
        $employee = $fund->organization->findEmployee($fund->organization->identity);

        // assert fund request visible when no state group specified
        $this->apiGetFundRequestsRequest($employee->organization, $employee, ['state_group' => 'all'])
            ->assertSuccessful()
            ->assertJsonPath('data.*.id', fn ($list) => in_array($fundRequest->id, $list));

        // assert pending fund request visible only on 'pending' state_group
        $this->apiGetFundRequestsRequest($employee->organization, $employee, ['state_group' => 'pending'])
            ->assertSuccessful()
            ->assertJsonPath('data.*.id', fn ($list) => in_array($fundRequest->id, $list));

        $this->apiGetFundRequestsRequest($employee->organization, $employee, ['state_group' => 'assigned'])
            ->assertSuccessful()
            ->assertJsonPath('data.*.id', fn ($list) => !in_array($fundRequest->id, $list));

        $this->apiGetFundRequestsRequest($employee->organization, $employee, ['state_group' => 'resolved'])
            ->assertSuccessful()
            ->assertJsonPath('data.*.id', fn ($list) => !in_array($fundRequest->id, $list));

        // assert assigned fund request visible only on 'assigned' state_group
        $this->apiFundRequestAssignRequest($fundRequest, $employee)->assertSuccessful();

        $this->apiGetFundRequestsRequest($employee->organization, $employee, ['state_group' => 'pending'])
            ->assertSuccessful()
            ->assertJsonPath('data.*.id', fn ($list) => !in_array($fundRequest->id, $list));

        $this->apiGetFundRequestsRequest($employee->organization, $employee, ['state_group' => 'assigned'])
            ->assertSuccessful()
            ->assertJsonPath('data.*.id', fn ($list) => in_array($fundRequest->id, $list));

        $this->apiGetFundRequestsRequest($employee->organization, $employee, ['state_group' => 'resolved'])
            ->assertSuccessful()
            ->assertJsonPath('data.*.id', fn ($list) => !in_array($fundRequest->id, $list));

        // assert resolved fund request visible only on 'assigned' resolved
        $this->apiFundRequestApproveRequest($fundRequest, $employee)->assertSuccessful();

        $this->apiGetFundRequestsRequest($employee->organization, $employee, ['state_group' => 'pending'])
            ->assertSuccessful()
            ->assertJsonPath('data.*.id', fn ($list) => !in_array($fundRequest->id, $list));

        $this->apiGetFundRequestsRequest($employee->organization, $employee, ['state_group' => 'assigned'])
            ->assertSuccessful()
            ->assertJsonPath('data.*.id', fn ($list) => !in_array($fundRequest->id, $list));

        $this->apiGetFundRequestsRequest($employee->organization, $employee, ['state_group' => 'resolved'])
            ->assertSuccessful()
            ->assertJsonPath('data.*.id', fn ($list) => in_array($fundRequest->id, $list));
    }

    /**
     * Check that employee can create and remove their own notes.
     *
     * @throws Throwable
     */
    public function testFundRequestEmployeeNote()
    {
        $fund = $this->setupNewFundAndCriteria();
        $employee = $fund->organization->findEmployee($fund->organization->identity);
        $fundRequest = $this->makeIdentityAndFundRequest($fund);
        $noteDescription = $this->faker->text();

        // assign fund request
        $this->apiFundRequestAssignRequest($fundRequest, $employee)->assertSuccessful();

        // assert note can be created
        $this->apiMakeFundRequestNoteRequest($fund->organization, $employee, $fundRequest, ['description' => $noteDescription])
            ->assertSuccessful()
            ->assertJsonPath('data.description', $noteDescription)
            ->assertJsonPath('data.employee.id', $employee->id);

        // assert the note visible in a list
        $this->apiGetFundRequestNotesRequest($fund->organization, $employee, $fundRequest)
            ->assertSuccessful()
            ->assertJsonPath('data', fn ($notes) => count($notes) === 1)
            ->assertJsonPath('data.0.id', $fundRequest->fresh()->notes[0]?->id);

        // assert note can be deleted
        $this->apiDeleteFundRequestNoteRequest($fund->organization, $employee, $fundRequest, $fundRequest->notes[0])
            ->assertSuccessful();

        $this->assertCount(0, $fundRequest->fresh()->notes);
    }

    /**
     * Check that the record can be edited (and it is only possible to change it to a valid value in terms of criteria).
     *
     * @throws Throwable
     */
    public function testFundRequestRecordEdit()
    {
        $fund = $this->setupNewFundAndCriteria();
        $fundRequest = $this->makeIdentityAndFundRequest($fund);
        $employee = $fund->organization->findEmployee($fund->organization->identity);
        $otherEmployee = $fund->organization->addEmployee($this->makeIdentity(), Role::pluck('id')->toArray());

        // assign fund request
        $this->apiFundRequestAssignRequest($fundRequest, $employee)->assertSuccessful();

        // assert can't update records when the organization does not allow it
        $fund->organization->forceFill(['allow_fund_request_record_edit' => false])->save();
        $this->apiUpdateFundRequestRecordRequest($fundRequest->records[0], ['value' => '4'], $employee)->assertForbidden();

        // allow updating records by organization
        $fund->organization->forceFill(['allow_fund_request_record_edit' => true])->save();

        // assert unassigned employee can't update records even when the organization allows it
        $this->apiUpdateFundRequestRecordRequest($fundRequest->records[0], ['value' => '4'], $otherEmployee)
            ->assertForbidden();

        // assert the record can only be updated with values still within fund criteria (>= 2 in this case)
        $this->apiUpdateFundRequestRecordRequest($fundRequest->records[0], ['value' => '1'], $employee)
            ->assertJsonValidationErrors(['value']);

        // assert the record can only be updated with values still within fund criteria (numeric in this case)
        $this->apiUpdateFundRequestRecordRequest($fundRequest->records[0], ['value' => 'string'], $employee)
            ->assertJsonValidationErrors(['value']);

        // assert record value can be updated by the assigned employee when value is within fund criteria
        $this->apiUpdateFundRequestRecordRequest($fundRequest->records[0], ['value' => '4'], $employee)
            ->assertSuccessful();

        // assert record was successfully updated
        $this->apiGetFundRequestRequest($fund->organization, $employee, $fundRequest)
            ->assertJsonPath('data.records.0.value', '4')
            ->assertSuccessful();
    }

    /**
     * Check that the record edit history is preserved and is available.
     * @throws Throwable
     */
    public function testFundRequestRecordEditHistory()
    {
        $fund = $this->setupNewFundAndCriteria();
        $fundRequest = $this->makeIdentityAndFundRequest($fund);
        $employee = $fund->organization->findEmployee($fund->organization->identity);

        $fund->organization->forceFill(['allow_fund_request_record_edit' => true])->save();

        $oldValue = $fundRequest->records[0]->value;
        $newValue = '3';

        // assert employee assignment
        $this->apiFundRequestAssignRequest($fundRequest, $employee)->assertSuccessful();

        // assert history updated
        $this->apiUpdateFundRequestRecordRequest($fundRequest->records[0], ['value' => $newValue], $employee)
            ->assertSuccessful()
            ->assertJsonPath('data.value', $newValue)
            ->assertJsonPath('data.history.0.old_value', $oldValue)
            ->assertJsonPath('data.history.0.new_value', $newValue)
            ->assertJsonPath('data.history.0.employee_email', $employee->identity->email);
    }

    /**
     * Check that files/attachments are available.
     * @throws Throwable
     */
    public function testFundRequestRecordFilesAreAccessible()
    {
        $fund = $this->setupNewFundAndCriteria(requireFiles: true);
        $fundRequest = $this->makeIdentityAndFundRequest($fund, filesPerRecord: 2);
        $employee = $fund->organization->findEmployee($fund->organization->identity);

        $this->apiFundRequestAssignRequest($fundRequest, $employee)->assertSuccessful();

        // assert fund request record's attachments are visible
        $this->apiGetFundRequestRequest($fund->organization, $employee, $fundRequest)
            ->assertJsonPath('data.records.0.files', fn ($files) => count($files) === 2)
            ->assertSuccessful();
    }

    /**
     * Check that record groups are filtered by scope (groups from other funds and organizations are excluded).
     *
     * @return void
     */
    public function testFundRequestRecordGroupsFilterByScope(): void
    {
        $fund = $this->setupNewFundAndCriteria();
        $organization = $fund->organization;
        $employee = $organization->findEmployee($organization->identity);

        $otherOrganization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $otherFundSameOrg = $this->makeTestFund($organization);
        $otherFundOtherOrg = $this->makeTestFund($otherOrganization);

        $recordTypeText = $this->makeRecordType($organization, RecordType::TYPE_STRING, 'group_scope_text');
        $recordTypeNumber = $this->makeRecordType($organization, RecordType::TYPE_NUMBER, 'group_scope_number');
        $recordTypeFlag = $this->makeRecordType($organization, RecordType::TYPE_STRING, 'group_scope_flag');

        $fund->criteria()->delete();
        $fund->criteria()->create([
            'value' => 'foo',
            'operator' => '=',
            'show_attachment' => false,
            'record_type_key' => $recordTypeText->key,
        ]);
        $fund->criteria()->create([
            'value' => 1,
            'operator' => '>=',
            'show_attachment' => false,
            'record_type_key' => $recordTypeNumber->key,
        ]);
        $fund->criteria()->create([
            'value' => 'yes',
            'operator' => '=',
            'show_attachment' => false,
            'record_type_key' => $recordTypeFlag->key,
        ]);

        $requester = $this->makeIdentity(email: $this->makeUniqueEmail(), bsn: 123456789);
        $fundRequest = $this->setCriteriaAndMakeFundRequest($requester, $fund, [
            $recordTypeText->key => 'foo',
            $recordTypeNumber->key => 2,
            $recordTypeFlag->key => 'yes',
        ]);

        $globalGroup = FundRequestRecordGroup::create([
            'title' => 'Global',
            'organization_id' => null,
            'fund_id' => null,
            'order' => 1,
        ]);
        $globalGroup->records()->create(['record_type_key' => $recordTypeText->key]);

        $orgGroup = FundRequestRecordGroup::create([
            'title' => 'Org',
            'organization_id' => $organization->id,
            'fund_id' => null,
            'order' => 2,
        ]);
        $orgGroup->records()->create(['record_type_key' => $recordTypeNumber->key]);

        $fundGroup = FundRequestRecordGroup::create([
            'title' => 'Fund',
            'organization_id' => $organization->id,
            'fund_id' => $fund->id,
            'order' => 3,
        ]);
        $fundGroup->records()->create(['record_type_key' => $recordTypeFlag->key]);

        $otherOrgGroup = FundRequestRecordGroup::create([
            'title' => 'Other org',
            'organization_id' => $otherOrganization->id,
            'fund_id' => null,
            'order' => 4,
        ]);
        $otherOrgGroup->records()->create(['record_type_key' => $recordTypeText->key]);

        $otherFundGroup = FundRequestRecordGroup::create([
            'title' => 'Other fund',
            'organization_id' => $organization->id,
            'fund_id' => $otherFundSameOrg->id,
            'order' => 5,
        ]);
        $otherFundGroup->records()->create(['record_type_key' => $recordTypeNumber->key]);

        $otherOrgFundGroup = FundRequestRecordGroup::create([
            'title' => 'Other org fund',
            'organization_id' => $otherOrganization->id,
            'fund_id' => $otherFundOtherOrg->id,
            'order' => 6,
        ]);
        $otherOrgFundGroup->records()->create(['record_type_key' => $recordTypeFlag->key]);

        Cache::store('array')->flush();

        $response = $this->apiGetFundRequestRequest($organization, $employee, $fundRequest)->assertSuccessful();
        $groupIds = collect($response->json('data.record_groups'))->pluck('id')->toArray();

        self::assertEqualsCanonicalizing([
            $globalGroup->id,
            $orgGroup->id,
            $fundGroup->id,
        ], $groupIds);
    }

    /**
     * Check that record type overlaps are assigned by priority and empty groups are filtered out.
     *
     * @return void
     */
    public function testFundRequestRecordGroupsAssignByPriority(): void
    {
        $fund = $this->setupNewFundAndCriteria();
        $organization = $fund->organization;
        $employee = $organization->findEmployee($organization->identity);

        $recordType = $this->makeRecordType($organization, RecordType::TYPE_STRING, 'group_priority_key');

        $fund->criteria()->delete();
        $fund->criteria()->create([
            'value' => 'foo',
            'operator' => '=',
            'show_attachment' => false,
            'record_type_key' => $recordType->key,
        ]);

        $requester = $this->makeIdentity(email: $this->makeUniqueEmail(), bsn: 123456789);
        $fundRequest = $this->setCriteriaAndMakeFundRequest($requester, $fund, [
            $recordType->key => 'foo',
        ]);

        $globalGroup = FundRequestRecordGroup::create([
            'title' => 'Global',
            'organization_id' => null,
            'fund_id' => null,
            'order' => 1,
        ]);
        $globalGroup->records()->create(['record_type_key' => $recordType->key]);

        $orgGroup = FundRequestRecordGroup::create([
            'title' => 'Org',
            'organization_id' => $organization->id,
            'fund_id' => null,
            'order' => 2,
        ]);
        $orgGroup->records()->create(['record_type_key' => $recordType->key]);

        $fundGroup = FundRequestRecordGroup::create([
            'title' => 'Fund',
            'organization_id' => $organization->id,
            'fund_id' => $fund->id,
            'order' => 3,
        ]);
        $fundGroup->records()->create(['record_type_key' => $recordType->key]);

        Cache::store('array')->flush();

        $response = $this->apiGetFundRequestRequest($organization, $employee, $fundRequest)->assertSuccessful();
        $recordGroups = $response->json('data.record_groups');

        self::assertCount(1, $recordGroups);
        self::assertSame($fundGroup->id, $recordGroups[0]['id']);
        self::assertEqualsCanonicalizing($fundRequest->records->pluck('id')->toArray(), $recordGroups[0]['record_ids']);
    }

    /**
     * Check that ungrouped records are listed under a "Without group" bucket only when needed.
     *
     * @return void
     */
    public function testFundRequestRecordGroupsIncludeUngroupedBucket(): void
    {
        $fund = $this->setupNewFundAndCriteria();
        $organization = $fund->organization;
        $employee = $organization->findEmployee($organization->identity);

        $recordTypeA = $this->makeRecordType($organization, RecordType::TYPE_STRING, 'group_ungrouped_a');
        $recordTypeB = $this->makeRecordType($organization, RecordType::TYPE_STRING, 'group_ungrouped_b');

        $fund->criteria()->delete();
        $fund->criteria()->create([
            'value' => 'foo',
            'operator' => '=',
            'show_attachment' => false,
            'record_type_key' => $recordTypeA->key,
        ]);
        $fund->criteria()->create([
            'value' => 'bar',
            'operator' => '=',
            'show_attachment' => false,
            'record_type_key' => $recordTypeB->key,
        ]);

        $requester = $this->makeIdentity(email: $this->makeUniqueEmail(), bsn: 123456789);
        $fundRequest = $this->setCriteriaAndMakeFundRequest($requester, $fund, [
            $recordTypeA->key => 'foo',
            $recordTypeB->key => 'bar',
        ]);

        $groupA = FundRequestRecordGroup::create([
            'title' => 'Group A',
            'organization_id' => null,
            'fund_id' => null,
            'order' => 1,
        ]);
        $groupA->records()->create(['record_type_key' => $recordTypeA->key]);

        Cache::store('array')->flush();

        $response = $this->apiGetFundRequestRequest($organization, $employee, $fundRequest)->assertSuccessful();
        $recordGroups = $response->json('data.record_groups');
        $ungrouped = collect($recordGroups)->firstWhere('id', 0);

        self::assertNotNull($ungrouped);
        self::assertEqualsCanonicalizing([
            $fundRequest->records->firstWhere('record_type_key', $recordTypeB->key)->id,
        ], $ungrouped['record_ids']);

        $groupB = FundRequestRecordGroup::create([
            'title' => 'Group B',
            'organization_id' => null,
            'fund_id' => null,
            'order' => 2,
        ]);
        $groupB->records()->create(['record_type_key' => $recordTypeB->key]);

        Cache::store('array')->flush();

        $response = $this->apiGetFundRequestRequest($organization, $employee, $fundRequest)->assertSuccessful();
        $recordGroups = $response->json('data.record_groups');

        self::assertNull(collect($recordGroups)->firstWhere('id', 0));
    }

    /**
     * Check that BSN records are hidden when bsn_enabled is false and groups update accordingly.
     *
     * @return void
     */
    public function testFundRequestRecordGroupsRespectBsnVisibility(): void
    {
        $fund = $this->setupNewFundAndCriteria();
        $organization = $fund->organization;
        $employee = $organization->findEmployee($organization->identity);

        $fund->criteria()->delete();
        $fund->criteria()->create([
            'value' => 2,
            'operator' => '>=',
            'show_attachment' => false,
            'record_type_key' => 'children_nth',
        ]);
        $fund->criteria()->create([
            'value' => '',
            'operator' => '*',
            'show_attachment' => false,
            'record_type_key' => 'bsn',
        ]);
        $fund->criteria()->create([
            'value' => '',
            'operator' => '*',
            'show_attachment' => false,
            'record_type_key' => 'partner_bsn',
        ]);

        $requester = $this->makeIdentity(email: $this->makeUniqueEmail(), bsn: 123456789);
        $fundRequest = $this->setCriteriaAndMakeFundRequest($requester, $fund, [
            'children_nth' => 2,
            'bsn' => '123456789',
            'partner_bsn' => '987654321',
        ]);

        $groupBsn = FundRequestRecordGroup::create([
            'title' => 'BSN Group',
            'organization_id' => null,
            'fund_id' => null,
            'order' => 1,
        ]);
        $groupBsn->records()->create(['record_type_key' => 'bsn']);

        $groupPartnerBsn = FundRequestRecordGroup::create([
            'title' => 'Partner BSN Group',
            'organization_id' => null,
            'fund_id' => null,
            'order' => 2,
        ]);
        $groupPartnerBsn->records()->create(['record_type_key' => 'partner_bsn']);

        Cache::store('array')->flush();

        $organization->update(['bsn_enabled' => false]);

        $response = $this->apiGetFundRequestRequest($organization, $employee, $fundRequest)->assertSuccessful();
        $recordKeys = collect($response->json('data.records'))->pluck('record_type_key')->toArray();
        $groupIds = collect($response->json('data.record_groups'))->pluck('id')->toArray();

        self::assertFalse(in_array('bsn', $recordKeys, true));
        self::assertFalse(in_array('partner_bsn', $recordKeys, true));
        self::assertFalse(in_array($groupBsn->id, $groupIds, true));
        self::assertFalse(in_array($groupPartnerBsn->id, $groupIds, true));

        $organization->update(['bsn_enabled' => true]);
        Cache::store('array')->flush();

        $response = $this->apiGetFundRequestRequest($organization, $employee, $fundRequest)->assertSuccessful();
        $recordKeys = collect($response->json('data.records'))->pluck('record_type_key')->toArray();
        $groupIds = collect($response->json('data.record_groups'))->pluck('id')->toArray();

        self::assertTrue(in_array('bsn', $recordKeys, true));
        self::assertTrue(in_array('partner_bsn', $recordKeys, true));
        self::assertTrue(in_array($groupBsn->id, $groupIds, true));
        self::assertTrue(in_array($groupPartnerBsn->id, $groupIds, true));
    }

    /**
     * Check that record_groups record_ids match visible records and records JSON is a list.
     *
     * @return void
     */
    public function testFundRequestRecordGroupsRecordIds(): void
    {
        $fund = $this->setupNewFundAndCriteria();
        $organization = $fund->organization;
        $employee = $organization->findEmployee($organization->identity);

        $recordTypeText = $this->makeRecordType($organization, RecordType::TYPE_STRING, 'shape_text');
        $recordTypeNumber = $this->makeRecordType($organization, RecordType::TYPE_NUMBER, 'shape_number');

        $fund->criteria()->delete();
        $fund->criteria()->create([
            'value' => 'foo',
            'operator' => '=',
            'show_attachment' => false,
            'record_type_key' => $recordTypeText->key,
        ]);
        $fund->criteria()->create([
            'value' => 1,
            'operator' => '>=',
            'show_attachment' => false,
            'record_type_key' => $recordTypeNumber->key,
        ]);
        $fund->criteria()->create([
            'value' => '',
            'operator' => '*',
            'show_attachment' => false,
            'record_type_key' => 'partner_bsn',
        ]);

        $requester = $this->makeIdentity(email: $this->makeUniqueEmail(), bsn: 123456789);
        $fundRequest = $this->setCriteriaAndMakeFundRequest($requester, $fund, [
            $recordTypeText->key => 'foo',
            $recordTypeNumber->key => 2,
            'partner_bsn' => '987654321',
        ]);

        $groupText = FundRequestRecordGroup::create([
            'title' => 'Text Group',
            'organization_id' => null,
            'fund_id' => null,
            'order' => 1,
        ]);
        $groupText->records()->create(['record_type_key' => $recordTypeText->key]);

        Cache::store('array')->flush();
        $organization->update(['bsn_enabled' => false]);

        $response = $this->apiGetFundRequestRequest($organization, $employee, $fundRequest)->assertSuccessful();
        $records = $response->json('data.records');

        self::assertTrue(array_is_list($records));

        $visibleRecordIds = collect($records)->pluck('id')->toArray();
        $recordGroups = $response->json('data.record_groups');
        $recordGroupIds = collect($recordGroups)->pluck('record_ids')->flatten()->toArray();

        self::assertEqualsCanonicalizing($visibleRecordIds, $recordGroupIds);
    }

    /**
     * @param bool $requireFiles
     * @return Fund
     */
    protected function setupNewFundAndCriteria(bool $requireFiles = false): Fund
    {
        // create sponsor and requester identities
        $sponsorIdentity = $this->makeIdentity(email: $this->makeUniqueEmail());

        // create the organization and fund
        $organization = $this->makeTestOrganization($sponsorIdentity);
        $fund = $this->makeTestFund($organization);

        $fund->criteria()->delete();

        $fund->criteria()->create([
            'value' => 2,
            'operator' => '>=',
            'show_attachment' => $requireFiles,
            'record_type_key' => 'children_nth',
        ]);

        return $fund;
    }

    /**
     * @param Fund $fund
     * @param int $filesPerRecord
     * @return FundRequest
     */
    protected function makeIdentityAndFundRequest(Fund $fund, int $filesPerRecord = 0): FundRequest
    {
        // create sponsor and requester identities
        $identity = $this->makeIdentity(email: $this->makeUniqueEmail(), bsn: 123456789);

        $records = [[
            'fund_criterion_id' => $fund->criteria[0]?->id,
            'value' => 5,
            'files' => array_map(fn () => $this->apiUploadFileRequest($identity, [
                'file' => UploadedFile::fake()->image('doc.jpg'),
                'type' => 'fund_request_record_proof',
            ])->json('data.uid'), $filesPerRecord > 0 ? range(1, $filesPerRecord) : []),
        ]];

        return FundRequest::find($this
            ->apiMakeFundRequestRequest($identity, $fund, ['records' => $records], false)
            ->assertSuccessful()
            ->json('data.id'));
    }
}
