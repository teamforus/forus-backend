<?php

namespace Tests\Feature;

use App\Enums\RecordValidationKey;
use App\Mail\Funds\FundRequests\FundRequestApprovedMail;
use App\Models\Employee;
use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\ProfileRecord;
use App\Models\RecordType;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use RuntimeException;
use Tests\TestCase;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestRecords;
use Throwable;

class FundRequestProfileCopyTest extends TestCase
{
    use DatabaseTransactions;
    use MakesTestFundRequests;
    use MakesTestFunds;
    use MakesTestOrganizations;
    use MakesTestRecords;

    /**
     * @throws Throwable
     * @return void
     */
    public function testManualApprovalCopiesProfileRecordsAndCreatesVoucher(): void
    {
        $this->freezeTime();

        [
            'request' => $request, 'employee' => $employee, 'name' => $name, 'date' => $date,
        ] = $this->makeProfileCopyRequest();

        $profile = $request->fund->organization->findOrMakeProfile($request->identity);
        $old = $this->makeProfileRecord($profile, $name, 'Old name');
        $this->travel(1)->seconds();

        $this->apiFundRequestApproveRequest($request, $employee, ['copy_to_profile' => true])->assertOk();

        $this->assertApprovedVoucher($request, 300);

        $this->assertSame(['Taylor', 'Old name'], $profile->profile_records()
            ->where('record_type_id', $name->id)->pluck('value')->all());

        $this->assertSame($old->id, $profile->profile_records()->where('record_type_id', $name->id)->get()->last()->id);

        foreach ([[$name, 'Taylor'], [$date, '1990-02-15']] as [$type, $value]) {
            $record = $profile->profile_records()->where('record_type_id', $type->id)->firstOrFail();
            $answer = $request->records()->where('record_type_key', $type->key)->sole();

            $this->assertSame($value, $record->value);
            $this->assertSame($employee->id, $record->employee_id);
            $this->assertNull($record->source);
            $this->assertSame($answer->getMorphClass(), $record->origin_type);
            $this->assertSame($answer->id, $record->origin_id);
        }
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testAmountSelectionApprovalCopiesProfileWithoutChangingSelectedAmount(): void
    {
        foreach (['custom', 'preset'] as $variant) {
            ['request' => $request, 'employee' => $employee, 'name' => $name] = $this->makeProfileCopyRequest();
            $request->fund->organization->forceFill(['allow_payouts' => true])->save();

            $request->fund->updateFundsConfig([
                'allow_custom_amounts_validator' => true, 'allow_preset_amounts_validator' => true,
                'custom_amount_min' => 100, 'custom_amount_max' => 200,
            ]);

            $payload = $variant === 'custom' ? ['amount' => 150] : [
                'fund_amount_preset_id' => $request->fund->amount_presets()->create([
                    'name' => 'Test amount', 'amount' => 175,
                ])->id,
            ];

            $this->apiFundRequestApproveRequest($request, $employee, [
                ...$payload, 'copy_to_profile' => true,
            ])->assertOk();

            $this->assertApprovedVoucher($request, $variant === 'custom' ? 150 : 175);

            $profile = $request->identity->profiles()->where('organization_id', $employee->organization_id)->sole();

            $this->assertSame('Taylor', $profile->profile_records()->where('record_type_id', $name->id)->sole()->value);
        }
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testManualApprovalWithoutCopyLeavesProfileUntouched(): void
    {
        foreach (['omitted' => [], 'disabled' => ['copy_to_profile' => false]] as $variant => $payload) {
            ['request' => $request, 'employee' => $employee, 'name' => $name] = $this->makeProfileCopyRequest();
            $profile = $request->fund->organization->findOrMakeProfile($request->identity);
            $this->makeProfileRecord($profile, $name, 'Keep this');
            $before = $profile->profile_records()->get()->toArray();

            $this->apiFundRequestApproveRequest($request, $employee, $payload)->assertOk();

            $this->assertApprovedVoucher($request, 300);
            $this->assertSame($before, $profile->profile_records()->get()->toArray(), $variant);
        }
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testAutomaticApprovalDoesNotCopyProfileRecords(): void
    {
        ['fund' => $fund, 'employee' => $employee, 'name' => $name] = $this->makeProfileCopyFund();
        $fund->update(['auto_requests_validation' => true, 'default_validator_employee_id' => $employee->id]);

        $criterion = $fund->criteria()->create([
            'record_type_key' => $name->key, 'operator' => '*', 'optional' => true, 'show_attachment' => false,
        ]);

        $identity = $this->makeIdentity($this->makeUniqueEmail(), $this->makeUniqueBsn());

        $response = $this->apiMakeFundRequestRequest($identity, $fund, ['records' => [
            $this->makeRequestCriterionValue($fund, 'children_nth', 5),
            ['fund_criterion_id' => $criterion->id, 'value' => 'Automatic name', 'files' => []],
        ]], false)->assertSuccessful();

        $request = FundRequest::findOrFail($response->json('data.id'));

        $this->assertApprovedVoucher($request, 300);
        $this->assertSame($employee->id, $request->employee_id);
        $this->assertFalse($identity->profiles()->where('organization_id', $fund->organization_id)->exists());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testCopyRequiresFundOptInAndProfileManagementPermission(): void
    {
        foreach (['fund_disabled', 'profiles_disabled', 'validator_only'] as $variant) {
            ['request' => $request, 'employee' => $employee, 'name' => $name] = $this->makeProfileCopyRequest();
            $organization = $request->fund->organization;
            $profile = $organization->findOrMakeProfile($request->identity);
            $this->makeProfileRecord($profile, $name, 'Protected value');

            if ($variant === 'fund_disabled') {
                $request->fund->fund_config->forceFill(['allow_fund_request_profile_copy' => false])->save();
            } elseif ($variant === 'profiles_disabled') {
                $organization->forceFill(['allow_profiles' => false])->save();
            } else {
                $employee = $this->makeTestEmployeeWithPermissions($organization, [Permission::VALIDATE_RECORDS]);
                $request->assignEmployee($employee);
            }

            $before = $this->copyState($request);

            $this->assertTrue(Gate::forUser($employee->identity)->allows('approveAsValidator', [
                $request->fresh(), $organization,
            ]));

            $preview = $this->apiFundRequestProfileCopyPreviewRequest($request, $employee);
            $this->assertSame(403, $preview->status(), $variant . ' preview');

            $approval = $this->apiFundRequestApproveRequest($request, $employee, ['copy_to_profile' => true]);
            $this->assertSame(403, $approval->status(), $variant . ' approval');

            $this->assertSame($before, $this->copyState($request), $variant);
        }
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testCopyPreviewReportsChangesWithoutWriting(): void
    {
        [
            'request' => $request, 'employee' => $employee, 'name' => $name, 'date' => $date,
        ] = $this->makeProfileCopyRequest();

        $profile = $request->fund->organization->findOrMakeProfile($request->identity);
        $this->makeProfileRecord($profile, $name, 'Previous name');
        $same = $this->makeProfileRecordType($request->fund->organization);
        $blank = $this->makeProfileRecordType($request->fund->organization);
        $this->makeProfileRecord($profile, $same, 'Same');
        $this->makeProfileRecord($profile, $blank, 'Keep');
        $request->records()->create(['record_type_key' => $same->key, 'value' => 'Same']);
        $request->records()->create(['record_type_key' => $blank->key, 'value' => ' ']);
        $before = $this->copyState($request);

        $response = $this->apiFundRequestProfileCopyPreviewRequest($request, $employee)->assertOk()
            ->assertJsonPath('profile_id', $profile->id);

        $records = collect($response->json('records'))->keyBy('record_type_key');

        foreach ([
            $name->key => 'update', $date->key => 'add', $same->key => 'unchanged', $blank->key => 'not_copied',
        ] as $key => $action) {
            $this->assertSame($action, $records[$key]['action']);
        }

        $this->assertSame('Previous name', $records[$name->key]['current_value']);
        $this->assertSame('Taylor', $records[$name->key]['application_value']);
        $this->assertSame('1990-02-15', $records[$date->key]['application_value']);
        $this->assertNull($records[$blank->key]['application_value']);
        $this->assertSame($before, $this->copyState($request));
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testApprovalRechecksValuesAfterCopyPreview(): void
    {
        $this->freezeTime();
        ['request' => $request, 'employee' => $employee, 'name' => $name] = $this->makeProfileCopyRequest();
        $profile = $request->fund->organization->findOrMakeProfile($request->identity);
        $this->makeProfileRecord($profile, $name, 'Old');
        $this->apiFundRequestProfileCopyPreviewRequest($request, $employee)->assertOk();
        $this->travel(1)->seconds();
        $profile->updateRecords([$name->key => 'Interim edit']);
        $request->records()->where('record_type_key', $name->key)->update(['value' => 'Fresh answer']);
        $this->travel(1)->seconds();

        $this->apiFundRequestApproveRequest($request, $employee, ['copy_to_profile' => true])->assertOk();

        $this->assertApprovedVoucher($request, 300);

        $this->assertSame(['Fresh answer', 'Interim edit', 'Old'], $profile->profile_records()
            ->where('record_type_id', $name->id)->pluck('value')->all());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testBlankAndUnsupportedAnswersDoNotOverwriteProfile(): void
    {
        [
            'request' => $request, 'employee' => $employee, 'name' => $name, 'date' => $date,
        ] = $this->makeProfileCopyRequest();

        $profile = $request->fund->organization->findOrMakeProfile($request->identity);
        $disabled = $this->makeProfileRecordType($request->fund->organization, ['profile_records' => false]);
        $keptDate = $this->makeProfileRecord($profile, $date, '1980-01-01');
        $keptDisabled = $this->makeProfileRecord($profile, $disabled, 'Keep disabled');
        $request->records()->where('record_type_key', $date->key)->update(['value' => '']);
        $request->records()->create(['record_type_key' => $disabled->key, 'value' => 'Do not copy']);

        $this->apiFundRequestApproveRequest($request, $employee, ['copy_to_profile' => true])->assertOk();

        $this->assertApprovedVoucher($request, 300);
        $this->assertSame('Taylor', $profile->profile_records()->where('record_type_id', $name->id)->sole()->value);

        $this->assertSame([$keptDate->id], $profile->profile_records()
            ->where('record_type_id', $date->id)->pluck('id')->all());

        $this->assertSame([$keptDisabled->id], $profile->profile_records()
            ->where('record_type_id', $disabled->id)->pluck('id')->all());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testCopyCreatesBlankProfileWhenNoAnswersAreCopyable(): void
    {
        [
            'request' => $request, 'employee' => $employee, 'name' => $name, 'date' => $date,
        ] = $this->makeProfileCopyRequest();

        $name->update(['profile_records' => false]);
        $date->update(['profile_records' => false]);
        RecordType::findByKey('children_nth')->update(['profile_records' => false]);

        $this->apiFundRequestProfileCopyPreviewRequest($request, $employee)->assertOk()
            ->assertExactJson(['profile_id' => null, 'records' => []]);

        $this->assertFalse($request->identity->profiles()->exists());

        $this->apiFundRequestApproveRequest($request, $employee, ['copy_to_profile' => true])->assertOk();

        $this->assertApprovedVoucher($request, 300);

        $profile = $request->identity->profiles()->where('organization_id', $employee->organization_id)->sole();

        $this->assertSame(0, $profile->profile_records()->count());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testIdenticalDuplicateAnswersAreCopiedOnce(): void
    {
        ['request' => $request, 'employee' => $employee, 'name' => $name] = $this->makeProfileCopyRequest();
        $first = $request->records()->where('record_type_key', $name->key)->sole();
        $request->records()->create(['record_type_key' => $name->key, 'value' => 'Taylor']);

        $preview = $this->apiFundRequestProfileCopyPreviewRequest($request, $employee)->assertOk();

        $this->assertCount(1, collect($preview->json('records'))->where('record_type_key', $name->key));

        $this->apiFundRequestApproveRequest($request, $employee, ['copy_to_profile' => true])->assertOk();

        $this->assertApprovedVoucher($request, 300);

        $profile = $request->identity->profiles()->where('organization_id', $employee->organization_id)->sole();
        $record = $profile->profile_records()->where('record_type_id', $name->id)->sole();

        $this->assertSame('Taylor', $record->value);
        $this->assertSame($first->id, $record->origin_id);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testConflictingOrInvalidAnswersRejectCopyAtomically(): void
    {
        foreach (['conflict', 'invalid'] as $variant) {
            ['request' => $request, 'employee' => $employee, 'name' => $name] = $this->makeProfileCopyRequest();

            if ($variant === 'conflict') {
                $request->records()->create(['record_type_key' => $name->key, 'value' => 'Different']);
            } else {
                $request->records()->where('record_type_key', $name->key)->update(['value' => 'A']);
            }

            $before = $this->copyState($request);

            $this->apiFundRequestProfileCopyPreviewRequest($request, $employee)
                ->assertUnprocessable()->assertJsonValidationErrors('records.' . $name->key);

            $this->apiFundRequestApproveRequest($request, $employee, ['copy_to_profile' => true])
                ->assertUnprocessable()->assertJsonValidationErrors('records.' . $name->key);

            $this->assertSame($before, $this->copyState($request), $variant);
        }
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testAwardFailureRollsBackApprovalAndCopiedProfile(): void
    {
        ['request' => $request, 'employee' => $employee] = $this->makeProfileCopyRequest();
        $before = $this->copyState($request);
        $copiedProfileId = null;
        $copiedRecordIds = [];
        $validatedRecordIds = [];
        $dispatcher = Voucher::getEventDispatcher();
        Voucher::setEventDispatcher(clone $dispatcher);

        try {
            Voucher::creating(function (Voucher $voucher) use (
                $request,
                &$copiedProfileId,
                &$copiedRecordIds,
                &$validatedRecordIds,
            ): void {
                if ($voucher->fund_request_id !== $request->id) {
                    return;
                }

                $profile = $request->identity->profiles()
                    ->where('organization_id', $request->fund->organization_id)->sole();

                $copiedProfileId = $profile->id;
                $copiedRecordIds = $profile->profile_records()->pluck('id')->all();

                $validatedRecordIds = $request->identity->records()
                    ->where('fund_request_id', $request->id)->pluck('id')->all();

                throw new RuntimeException('Injected award failure.');
            });

            $this->apiFundRequestApproveRequest($request, $employee, ['copy_to_profile' => true])
                ->assertStatus(500);
        } finally {
            Voucher::setEventDispatcher($dispatcher);
        }

        $this->assertNotNull($copiedProfileId);
        $this->assertNotEmpty($copiedRecordIds);
        $this->assertNotEmpty($validatedRecordIds);
        $this->assertDatabaseMissing('profiles', ['id' => $copiedProfileId]);
        $this->assertSame(0, ProfileRecord::whereIn('id', $copiedRecordIds)->count());
        $this->assertSame($before, $this->copyState($request));
        $this->assertMailableNotSent($request->identity->email, FundRequestApprovedMail::class);
    }

    /**
     * @throws Throwable
     * @return array{request: FundRequest, employee: Employee, name: RecordType, date: RecordType}
     */
    private function makeProfileCopyRequest(): array
    {
        ['fund' => $fund, 'employee' => $employee, 'name' => $name, 'date' => $date] = $this->makeProfileCopyFund();
        $identity = $this->makeIdentity($this->makeUniqueEmail(), $this->makeUniqueBsn());
        $request = $this->setCriteriaAndMakeFundRequest($identity, $fund, ['children_nth' => 5]);
        $request->assignEmployee($employee);
        $request->records()->create(['record_type_key' => $name->key, 'value' => 'Taylor']);
        $request->records()->create(['record_type_key' => $date->key, 'value' => '15-02-1990']);

        return compact('request', 'employee', 'name', 'date');
    }

    /**
     * @throws Throwable
     * @return array{fund: Fund, employee: Employee, name: RecordType, date: RecordType}
     */
    private function makeProfileCopyFund(): array
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()), [
            'allow_profiles' => true,
            'fund_request_resolve_policy' => Organization::FUND_REQUEST_POLICY_AUTO_REQUESTED,
        ]);

        $fund = $this->makeTestFund($organization, fundConfigsData: ['allow_fund_request_profile_copy' => true]);
        $employee = $organization->findEmployee($organization->identity);
        $name = $this->makeCriteriaRecordType($organization, RecordType::TYPE_STRING, 'text');
        $name->update(['profile_records' => true, 'validation_key' => RecordValidationKey::PERSON_NAME]);
        $date = $this->makeProfileRecordType($organization, ['type' => RecordType::TYPE_DATE]);

        return compact('fund', 'employee', 'name', 'date');
    }

    /**
     * @param FundRequest $request
     * @param int $amount
     * @return void
     */
    private function assertApprovedVoucher(FundRequest $request, int $amount): void
    {
        $this->assertSame(FundRequest::STATE_APPROVED, $request->refresh()->state);
        $voucher = $request->vouchers()->sole();

        $this->assertSame($request->identity_id, $voucher->identity_id);
        $this->assertSame($request->fund_id, $voucher->fund_id);
        $this->assertEquals($amount, $voucher->amount);
    }

    /**
     * @param FundRequest $request
     * @return array
     */
    private function copyState(FundRequest $request): array
    {
        return [
            'request' => $request->fresh()->getAttributes(),
            'answers' => $request->records()->orderBy('id')->get()->toArray(),
            'records' => $request->identity->records()->with('validations')->orderBy('id')->get()->toArray(),
            'profiles' => $request->identity->profiles()->with('profile_records')->orderBy('id')->get()->toArray(),
            'vouchers' => $request->vouchers()->get()->toArray(),
            'logs' => $request->logs()->get()->toArray(),
        ];
    }
}
