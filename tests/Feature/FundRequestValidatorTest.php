<?php

namespace Feature;

use App\Models\FundRequest;
use App\Models\FundRequestClarification;
use App\Models\Identity;
use App\Models\Note;
use App\Models\Organization;
use App\Models\Role;
use App\Services\MediaService\Traits\UsesMediaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
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
     * Check that the acceptance with amount presets is working and only allows predefined values.
     * @throws Throwable
     */
    public function testFundRequestApproveWithAmountPresets()
    {
        $fundRequest = $this->prepareFundRequest();
        $fund = $fundRequest->fund;
        $organization = $fund->organization;

        $organization->forceFill([
            'allow_payouts' => true,
            'fund_request_resolve_policy' => Organization::FUND_REQUEST_POLICY_AUTO_REQUESTED,
        ])->save();

        $preset = $fund->amount_presets()->create([
            'name' => 'AMOUNT OPTION PRESET',
            'amount' => 100,
        ]);

        $fundRequest->assignEmployee($organization->findEmployee($organization->identity));

        // assert validation error if allow_preset_amounts_validator is false
        $fund->updateFundsConfig(['allow_preset_amounts_validator' => false]);

        $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/approve",
            ['fund_amount_preset_id' => $preset->id],
            $this->makeApiHeaders($organization->identity),
        )->assertJsonValidationErrors(['fund_amount_preset_id']);

        // assert validation error if allow_preset_amounts_validator is true but preset from another fund
        $fund->updateFundsConfig(['allow_preset_amounts_validator' => true]);

        $otherFund = $this->makeTestFund($organization);
        $otherFund->updateFundsConfig(['allow_preset_amounts_validator' => true]);
        $presetOtherFund = $otherFund->amount_presets()->create([
            'name' => 'AMOUNT OPTION PRESET SECOND FUND',
            'amount' => 100,
        ]);

        $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/approve",
            ['fund_amount_preset_id' => $presetOtherFund->id],
            $this->makeApiHeaders($organization->identity),
        )->assertJsonValidationErrors(['fund_amount_preset_id']);

        // assert success
        $fund->updateFundsConfig(['allow_preset_amounts_validator' => true]);

        $preset = $fund->amount_presets()->create([
            'name' => 'AMOUNT OPTION PRESET',
            'amount' => 100,
        ]);

        $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/approve",
            ['fund_amount_preset_id' => $preset->id],
            $this->makeApiHeaders($organization->identity),
        )->assertSuccessful();

        $this->assertEquals(FundRequest::STATE_APPROVED, $fundRequest->fresh()->state);
        $this->assertEquals($preset->amount, $fundRequest->vouchers()->first()->amount);
    }

    /**
     * Check that the acceptance with custom amount is also working and only allows custom values within defined ranges.
     * @throws Throwable
     */
    public function testFundRequestApproveWithCustomAmount()
    {
        $fundRequest = $this->prepareFundRequest();
        $fund = $fundRequest->fund;
        $organization = $fund->organization;

        $organization->forceFill([
            'allow_payouts' => true,
            'fund_request_resolve_policy' => Organization::FUND_REQUEST_POLICY_AUTO_REQUESTED,
        ])->save();

        $fund->updateFundsConfig([
            'custom_amount_min' => 100,
            'custom_amount_max' => 200,
        ]);

        $fundRequest->assignEmployee($organization->findEmployee($organization->identity));

        // assert validation error if allow_custom_amounts_validator is false
        $fund->updateFundsConfig(['allow_custom_amounts_validator' => false]);

        $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/approve",
            ['amount' => 150],
            $this->makeApiHeaders($organization->identity),
        )->assertJsonValidationErrors(['amount']);

        // assert validation error if allow_custom_amounts_validator is true but amount not between configs
        $fund->updateFundsConfig(['allow_custom_amounts_validator' => true]);

        $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/approve",
            ['amount' => 300],
            $this->makeApiHeaders($organization->identity),
        )->assertJsonValidationErrors(['amount']);

        // assert success with custom amount
        $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/approve",
            ['amount' => 150],
            $this->makeApiHeaders($organization->identity),
        )->assertSuccessful();

        $this->assertEquals(FundRequest::STATE_APPROVED, $fundRequest->fresh()->state);
        $this->assertEquals(150, $fundRequest->vouchers()->first()->amount);
    }

    /**
     * Check that partner bsn can be assigned.
     * @throws Throwable
     */
    public function testFundRequestAssignPartnerBsn()
    {
        $fundRequest = $this->prepareFundRequest();
        $organization = $fundRequest->fund->organization;
        $organization->update(['bsn_enabled' => false]);

        $fundRequest->assignEmployee($organization->findEmployee($organization->identity))->refresh();

        $data = [
            'value' => 123456781,
            'record_type_key' => 'partner_bsn',
        ];

        // assert validation error for record_type_key if bsn_enabled is false
        $this->postJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/records",
            $data,
            $this->makeApiHeaders($fundRequest->employee->identity),
        )->assertJsonValidationErrors(['record_type_key']);

        // assert success if bsn_enabled is true
        $organization->update(['bsn_enabled' => true]);

        $this->postJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/records",
            $data,
            $this->makeApiHeaders($fundRequest->employee->identity),
        )->assertSuccessful();

        $record = $fundRequest->records()->where('record_type_key', 'partner_bsn')->first();
        $this->assertNotNull($record);
        $this->assertEquals($data['value'], $record->value);
    }

    /**
     * Check that fund-request can be accepted, refused or dismissed.
     * @throws Throwable
     */
    public function testFundRequestActions()
    {
        $fundRequest = $this->prepareFundRequest();
        $organization = $fundRequest->fund->organization;

        $fundRequest->assignEmployee($organization->findEmployee($organization->identity));

        DB::beginTransaction();
        $this->approveFundRequest($organization, $fundRequest);
        DB::rollBack();

        DB::beginTransaction();
        $this->disregardFundRequest($organization, $fundRequest);
        DB::rollBack();

        DB::beginTransaction();
        $this->declineFundRequest($organization, $fundRequest);
        DB::rollBack();
    }

    /**
     * @throws Throwable
     */
    public function testFundRequestAccessible()
    {
        $fundRequest = $this->prepareFundRequest();
        $organization = $fundRequest->fund->organization;

        // assert access for organization identity
        $this->assertAccessible($organization, $organization->identity, $fundRequest, true);

        // assert access for organization employee with correct permissions
        $employee = $organization->addEmployee(
            $this->makeIdentity($this->makeUniqueEmail()),
            Role::pluck('id')->toArray(),
        );

        $this->assertAccessible($organization, $employee->identity, $fundRequest, true);

        // assert forbidden for organization employee without correct permissions
        $employee = $organization->addEmployee($this->makeIdentity($this->makeUniqueEmail()));
        $this->assertAccessible($organization, $employee->identity, $fundRequest, false);

        // assert forbidden for employee with correct permissions from another organization
        $otherOrganization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));

        $employee = $otherOrganization->addEmployee(
            $this->makeIdentity($this->makeUniqueEmail()),
            Role::pluck('id')->toArray(),
        );

        $this->assertAccessible($organization, $employee->identity, $fundRequest, false);
    }

    /**
     * Check that the employee can assign (when not already assigned) and resign (when already assigned) himself.
     * @return void
     */
    public function testFundRequestAssign()
    {
        $fundRequest = $this->prepareFundRequest();
        $organization = $fundRequest->fund->organization;

        // create employee with correct permissions
        $employee = $organization->addEmployee(
            $this->makeIdentity($this->makeUniqueEmail()),
            Role::pluck('id')->toArray(),
        );

        // create employee without correct permissions
        $employeeNoPermissions = $organization->addEmployee($this->makeIdentity($this->makeUniqueEmail()));

        // assert forbidden assign as the employee doesn't have correct permissions
        $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/assign",
            [],
            $this->makeApiHeaders($employeeNoPermissions->identity)
        )->assertForbidden();

        // assert successful assign
        $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/assign",
            [],
            $this->makeApiHeaders($employee->identity)
        )->assertSuccessful();

        $this->assertEquals($employee->id, $fundRequest->refresh()->employee_id);

        // assert forbidden assign as already assigned
        $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/assign",
            [],
            $this->makeApiHeaders($employee->identity)
        )->assertForbidden();

        // assert successful resign
        $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/resign",
            [],
            $this->makeApiHeaders($employee->identity)
        )->assertSuccessful();

        $this->assertNull($fundRequest->refresh()->employee_id);

        // assert forbidden resign as nobody assigned
        $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/resign",
            [],
            $this->makeApiHeaders($employee->identity)
        )->assertForbidden();
    }

    /**
     * Check that employee managers can assign (when not already assigned) and
     * resign (when already assigned) other employees with the correct permissions.
     * @return void
     */
    public function testFundRequestAssignEmployeeBySupervisor()
    {
        $fundRequest = $this->prepareFundRequest();
        $organization = $fundRequest->fund->organization;

        $supervisorEmployee = $organization->addEmployee(
            $this->makeIdentity($this->makeUniqueEmail()),
            Role::where('key', 'supervisor_validator')->pluck('id')->toArray(),
        );

        // create employee with correct permissions
        $employee = $organization->addEmployee(
            $this->makeIdentity($this->makeUniqueEmail()),
            Role::where('key', 'validation')->pluck('id')->toArray(),
        );

        // create employee without correct permissions
        $employeeNoPermissions = $organization->addEmployee($this->makeIdentity($this->makeUniqueEmail()));

        // assert forbidden assign employee as another employee that doesn't have correct permissions
        $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/assign-employee",
            ['employee_id' => $employee->id],
            $this->makeApiHeaders($employee->identity)
        )->assertForbidden();

        // validation errors for employee without correct permissions
        $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/assign-employee",
            ['employee_id' => $employeeNoPermissions->id],
            $this->makeApiHeaders($supervisorEmployee->identity)
        )->assertJsonValidationErrors(['employee_id']);

        // assert successful assign employee
        $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/assign-employee",
            ['employee_id' => $employee->id],
            $this->makeApiHeaders($supervisorEmployee->identity)
        )->assertSuccessful();

        $this->assertEquals($employee->id, $fundRequest->refresh()->employee_id);

        // assert successful resign
        $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/resign-employee",
            [],
            $this->makeApiHeaders($supervisorEmployee->identity)
        )->assertSuccessful();

        $this->assertNull($fundRequest->refresh()->employee_id);
    }

    /**
     * Check that record clarification request can be send, and when answered, it can be seen in the table.
     * @throws Throwable
     */
    public function testFundRequestClarificationAnswer()
    {
        $fundRequest = $this->prepareFundRequest();
        $organization = $fundRequest->fund->organization;

        $questionToken = $this->requestFundRequestClarification($organization, $fundRequest);

        $clarifications = $fundRequest->clarifications()->get();
        $this->assertCount(1, $clarifications);

        $clarification = $clarifications[0];
        $this->assertEquals($questionToken, $clarification->question);

        $this->answerOnFundRequestClarification($fundRequest, $clarification);

        $response = $this->getJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id",
            $this->makeApiHeaders($organization->identity)
        );

        $response->assertSuccessful();
        $response->assertJsonPath('data.records.0.clarifications.0.answer', $clarification->answer);
    }

    /**
     * Check that requests are shown on the correct tab: state_group param and can be searched by string.
     * @throws Throwable
     */
    public function testFundRequestFilterByState()
    {
        $fundRequest = $this->prepareFundRequest();
        $organization = $fundRequest->fund->organization;
        $sponsorIdentity = $organization->identity;

        $this->assertExistInList($organization, $sponsorIdentity, $fundRequest, ['state_group' => 'all']);

        // pending
        $this->assertExistInList($organization, $sponsorIdentity, $fundRequest, ['state_group' => 'pending']);
        $this->assertExistInList($organization, $sponsorIdentity, $fundRequest, ['state_group' => 'assigned'], false);
        $this->assertExistInList($organization, $sponsorIdentity, $fundRequest, ['state_group' => 'resolved'], false);

        // assigned
        $fundRequest->assignEmployee($organization->findEmployee($sponsorIdentity));
        $this->assertExistInList($organization, $sponsorIdentity, $fundRequest, ['state_group' => 'assigned']);
        $this->assertExistInList($organization, $sponsorIdentity, $fundRequest, ['state_group' => 'pending'], false);
        $this->assertExistInList($organization, $sponsorIdentity, $fundRequest, ['state_group' => 'resolved'], false);

        // resolved
        $this->approveFundRequest($organization, $fundRequest);
        $this->assertExistInList($organization, $sponsorIdentity, $fundRequest, ['state_group' => 'resolved']);
        $this->assertExistInList($organization, $sponsorIdentity, $fundRequest, ['state_group' => 'pending'], false);
        $this->assertExistInList($organization, $sponsorIdentity, $fundRequest, ['state_group' => 'assigned'], false);
    }

    /**
     * Check that employee can create and remove their own notes.
     * @throws Throwable
     */
    public function testFundRequestEmployeeNote()
    {
        $fundRequest = $this->prepareFundRequest();
        $organization = $fundRequest->fund->organization;

        $fundRequest->assignEmployee($organization->findEmployee($organization->identity));

        // add a note by employee
        $noteData = ['description' => $this->faker->text()];

        $response = $this->postJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/notes",
            $noteData,
            $this->makeApiHeaders($organization->identity)
        );

        $response->assertSuccessful();

        $response->assertJsonStructure([
            'data' => [
                'id',
                'description',
                'created_at',
                'created_at_locale',
                'employee' => [
                    'id', 'email', 'identity_address',
                ],
            ],
        ]);

        $note = Note::find($response->json('data.id'));
        $this->assertNotNull($note);

        // assert the note visible in a list
        $response = $this->getJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/notes",
            $this->makeApiHeaders($organization->identity)
        );

        $response->assertSuccessful();
        $noteItem = collect($response->json('data'))->first(fn ($item) => $item['id'] === $note->id);
        $this->assertNotNull($noteItem);

        // delete note
        $this->deleteJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/notes/$note->id",
            [],
            $this->makeApiHeaders($organization->identity)
        )->assertSuccessful();

        $note = Note::find($note->id);
        $this->assertNull($note);
    }

    /**
     * Check that the record can be edited (and it is only possible to change it to a valid value in terms of criteria).
     * @throws Throwable
     */
    public function testFundRequestRecordEdit()
    {
        $fundRequest = $this->prepareFundRequest();
        $organization = $fundRequest->fund->organization;

        $fundRequest->assignEmployee($organization->findEmployee($organization->identity));
        $fundRequest->refresh();

        // the first record is children_nth, and it can be an int and >= 2
        $record = $fundRequest->records()->first();

        // assert forbidden as the organization doesn't allow record edit
        $organization->forceFill(['allow_fund_request_record_edit' => false])->save();

        $this
            ->updateFundRequestRecordRequest($fundRequest, $record, 4, $fundRequest->employee)
            ->assertForbidden();

        // assert forbidden as the organization allows record edit but not assigned employee is used
        $organization->forceFill(['allow_fund_request_record_edit' => true])->save();
        $otherEmployee = $organization->addEmployee($this->makeIdentity(), Role::pluck('id')->toArray());

        $this
            ->updateFundRequestRecordRequest($fundRequest, $record, 4, $otherEmployee)
            ->assertForbidden();

        // assert validation errors if the value is int and less than 2
        $this
            ->updateFundRequestRecordRequest($fundRequest, $record, 1, $fundRequest->employee)
            ->assertJsonValidationErrors(['value']);

        // assert validation errors if the value is string
        $this
            ->updateFundRequestRecordRequest($fundRequest, $record, 'string', $fundRequest->employee)
            ->assertJsonValidationErrors(['value']);

        // assert valid value for record
        $this
            ->updateFundRequestRecordRequest($fundRequest, $record, 4, $fundRequest->employee)
            ->assertSuccessful();

        $this->assertEquals(4, $record->refresh()->value);
    }

    /**
     * Check that the record edit history is preserved and is available.
     * @throws Throwable
     */
    public function testFundRequestRecordEditHistory()
    {
        $fundRequest = $this->prepareFundRequest();
        $organization = $fundRequest->fund->organization;
        $organization->forceFill(['allow_fund_request_record_edit' => true])->save();

        $oldValue = $fundRequest->records()->first()->value;
        $newValue = 3;

        $fundRequest->assignEmployee($organization->findEmployee($organization->identity));
        $fundRequest->refresh();

        // the first record is children_nth, and it can be an int and >= 2
        $record = $fundRequest->records()->first();

        $this
            ->updateFundRequestRecordRequest($fundRequest, $record, $newValue, $fundRequest->employee)
            ->assertSuccessful();

        $this->assertEquals($newValue, $record->refresh()->value);

        // assert record history exists
        $response = $this->getJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id",
            $this->makeApiHeaders($fundRequest->employee->identity),
        );

        $response->assertSuccessful();
        $history = $response->json('data.records.0.history');
        $history = collect($history)->map(fn ($item) => Arr::only($item, ['new_value', 'old_value', 'employee_email']));

        $this->assertEquals([
            ['new_value' => $newValue, 'old_value' => $oldValue, 'employee_email' => $organization->identity->email],
        ], $history->toArray());
    }

    /**
     * Check that files/attachments are available.
     * @throws Throwable
     */
    public function testFundRequestRecordFiles()
    {
        $fundRequest = $this->prepareFundRequest(2);
        $organization = $fundRequest->fund->organization;
        $fundRequest->assignEmployee($organization->findEmployee($organization->identity));
        $fundRequest->refresh();

        // assert record files exists
        $response = $this->getJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id",
            $this->makeApiHeaders($fundRequest->employee->identity),
        );

        $response->assertSuccessful();
        $this->assertCount(2, $response->json('data.records.0.files'));
    }

    /**
     * @param int $fileCountPerRecord
     * @return FundRequest
     */
    protected function prepareFundRequest(int $fileCountPerRecord = 0): FundRequest
    {
        // create sponsor and requester identities
        $sponsorIdentity = $this->makeIdentity(email: $this->makeUniqueEmail());
        $requesterIdentity = $this->makeIdentity(email: $this->makeUniqueEmail(), bsn: 123456789);

        // create the organization and fund
        $organization = $this->makeTestOrganization($sponsorIdentity);
        $fund = $this->makeTestFund($organization);

        $fund->criteria()->delete();

        $fund->criteria()->create([
            'value' => 2,
            'operator' => '>=',
            'show_attachment' => $fileCountPerRecord > 0,
            'record_type_key' => 'children_nth',
        ]);

        $files = [];

        for ($i = 0; $i < $fileCountPerRecord; $i++) {
            $file = $this->makeRecordProofFile($this->makeApiHeaders($this->makeIdentityProxy($requesterIdentity)));
            $files = [
                ...$files,
                $file->json('data.uid'),
            ];
        }

        $records = [[
            'fund_criterion_id' => $fund->criteria[0]?->id,
            'value' => 5,
            'files' => $files,
        ]];

        $response = $this->makeFundRequest($requesterIdentity, $fund, $records, false);
        $response->assertSuccessful();
        /** @var FundRequest $fundRequest */
        $fundRequest = FundRequest::find($response->json('data.id'));
        $this->assertNotNull($fundRequest);

        return $fundRequest;
    }

    /**
     * @param array $headers
     * @return \Illuminate\Testing\TestResponse
     */
    protected function makeRecordProofFile(array $headers): TestResponse
    {
        $type = 'fund_request_record_proof';
        $filePath = base_path('tests/assets/test.png');
        $file = UploadedFile::fake()->createWithContent($this->faker()->uuid . '.png', $filePath);

        $response = $this->postJson('/api/v1/files', compact('type', 'file'), $headers);
        $response->assertCreated();

        return $response;
    }

    /**
     * @param Organization $organization
     * @param Identity $identity
     * @param FundRequest $fundRequest
     * @param bool $assertSuccess
     * @return void
     */
    protected function assertAccessible(
        Organization $organization,
        Identity $identity,
        FundRequest $fundRequest,
        bool $assertSuccess
    ): void {
        if ($assertSuccess) {
            $this->assertExistInList($organization, $identity, $fundRequest);

            $response = $this->getJson(
                "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id",
                $this->makeApiHeaders($identity)
            );

            $response->assertSuccessful();
            $response->assertJsonStructure(Arr::undot($this->fundRequestStructure));
        } else {
            $this->getJson(
                "/api/v1/platform/organizations/$organization->id/fund-requests",
                $this->makeApiHeaders($identity)
            )->assertForbidden();

            $this->getJson(
                "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id",
                $this->makeApiHeaders($identity)
            )->assertForbidden();
        }
    }

    /**
     * @param Organization $organization
     * @param Identity $identity
     * @param FundRequest $fundRequest
     * @param array $query
     * @param bool $assertExists
     * @return void
     */
    protected function assertExistInList(
        Organization $organization,
        Identity $identity,
        FundRequest $fundRequest,
        array $query = [],
        bool $assertExists = true,
    ): void {
        $response = $this->getJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests?" . http_build_query($query),
            $this->makeApiHeaders($identity)
        );

        $response->assertSuccessful();
        $requestArr = array_first($response->json('data'), fn (array $item) => $item['id'] === $fundRequest->id);

        $assertExists ? $this->assertNotNull($requestArr) : $this->assertNull($requestArr);
    }

    /**
     * @param FundRequest $fundRequest
     * @param FundRequestClarification $clarification
     * @return void
     */
    protected function answerOnFundRequestClarification(
        FundRequest $fundRequest,
        FundRequestClarification $clarification
    ): void {
        $this->patchJson(
            "/api/v1/platform/fund-requests/$fundRequest->id/clarifications/$clarification->id",
            [
                'answer' => 'answer',
                'files' => [],
            ],
            $this->makeApiHeaders($fundRequest->identity)
        )->assertSuccessful();

        $clarification->refresh();
        $this->assertEquals('answer', $clarification->answer);
        $this->assertEquals(FundRequestClarification::STATE_ANSWERED, $clarification->state);
        $this->assertNotNull($clarification->answered_at);

    }

    /**
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @param array $data
     * @return void
     */
    protected function approveFundRequest(
        Organization $organization,
        FundRequest $fundRequest,
        array $data = [],
    ): void {
        $response = $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/approve",
            $data,
            $this->makeApiHeaders($organization->identity),
        );

        $response->assertSuccessful();
    }

    /**
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @return void
     */
    protected function assertFundRequestApprovedEmailLog(
        Organization $organization,
        FundRequest $fundRequest,
    ): void {
        // assert email log exists
        $response = $this->getJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/email-logs",
            $this->makeApiHeaders($organization->identity),
        );

        $response->assertSuccessful();
        $data = $response->json('data');

        self::assertCount(2, $data);
        self::assertCount(1, Arr::where($data, fn ($item) => $item['type'] == 'fund_request_approved'));
    }

    /**
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @param bool $notify
     * @return void
     */
    protected function disregardFundRequest(
        Organization $organization,
        FundRequest $fundRequest,
        bool $notify = true
    ): void {
        $response = $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/disregard",
            compact('notify'),
            $this->makeApiHeaders($organization->identity),
        );

        $response->assertSuccessful();
    }

    /**
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @return void
     */
    protected function declineFundRequest(Organization $organization, FundRequest $fundRequest): void
    {
        $response = $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/decline",
            [],
            $this->makeApiHeaders($organization->identity),
        );

        $response->assertSuccessful();
    }

    /**
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @param bool $notify
     * @return void
     */
    protected function assertFundRequestDisregardedEmailLog(
        Organization $organization,
        FundRequest $fundRequest,
        bool $notify,
    ): void {
        // assert email log exists
        $response = $this->getJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/email-logs",
            $this->makeApiHeaders($organization->identity),
        );

        $response->assertSuccessful();
        $data = $response->json('data');

        self::assertCount($notify ? 2 : 1, $data);
        self::assertCount(
            $notify ? 1 : 0,
            Arr::where($data, fn ($item) => $item['type'] == 'fund_request_disregarded'),
        );
    }
}
