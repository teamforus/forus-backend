<?php

namespace Tests\Traits;

use App\Models\Fund;
use App\Models\FundCriterion;
use App\Models\Organization;
use App\Models\PrevalidationRequest;
use App\Models\PrevalidationRequestMissedRecord;
use App\Models\PrevalidationRequestRecord;
use App\Models\RecordType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Config;
use Throwable;

trait MakesTestPrevalidationRequests
{
    /**
     * @param Organization $organization
     * @return void
     */
    protected function enablePrevalidationRequestForOrganization(Organization $organization): void
    {
        $organization->forceFill(['allow_prevalidation_requests' => true])->save();
    }

    /**
     * @param Organization $organization
     * @return Fund
     */
    protected function makePrevalidationRequestCsvFund(Organization $organization): Fund
    {
        return $this->makeTestFund($organization, [
            'type' => 'budget',
        ], [
            'allow_fund_request_prefill' => true,
            'allow_prevalidations' => false,
            'key' => 'nijmegen-vi',
        ]);
    }

    /**
     * @param array $personBsnOverrides
     * @param bool $includeRequiredIncomeGroup
     * @return array{organization: Organization, fund: Fund, prefillKey: string, manualKey: string}
     */
    protected function makePrevalidationRequestCsvFixture(
        array $personBsnOverrides = [],
        bool $includeRequiredIncomeGroup = false,
    ): array {
        $this->fakePersonBsnApiResponses($personBsnOverrides);

        $organization = $this->makeTestOrganization($this->makeIdentity());
        $this->enablePersonBsnApiForOrganization($organization);
        $this->enablePrevalidationRequestForOrganization($organization);

        $fund = $this->makePrevalidationRequestCsvFund($organization);

        return [
            ...compact('organization', 'fund'),
            ...$this->makeDefaultPrevalidationRequestCsvCriteria($organization, $fund, $includeRequiredIncomeGroup),
        ];
    }

    /**
     * @throws Throwable
     * @return array{organization: Organization, fund: Fund, prevalidationRequest: PrevalidationRequest}
     */
    protected function makePrevalidationRequestWithMissingRecords(): array
    {
        Config::set('forus.person_bsn.test_response_profile', 'missed_records');

        $this->fakePersonBsnApiResponses();

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

        $this->makeFundCriteria($fund, [[
            'title' => 'Children count',
            'value' => 1,
            'operator' => '>=',
            'optional' => true,
            'record_type_key' => Fund::RECORD_TYPE_KEY_CHILDREN_SAME_ADDRESS,
            'show_attachment' => false,
            'fill_type' => FundCriterion::FILL_TYPE_PREFILL,
        ]]);

        $requestDataPrefill = [
            'bsn' => '999993112',
            'uid' => token_generator()->generate(32),
        ];

        $this->apiMakePrevalidationRequestCollectionRequest($organization, [
            'fund_id' => $fund->id,
            'data' => [$requestDataPrefill],
        ])->assertSuccessful();

        $prevalidationRequest = $this->assertPrevalidationRequestCreated($fund, $requestDataPrefill);
        $prevalidationRequest->makePrevalidation();

        $this->assertPrevalidationRequestMissingRecordsState($prevalidationRequest);

        return compact('organization', 'fund', 'prevalidationRequest');
    }

    /**
     * @param PrevalidationRequest $prevalidationRequest
     * @return Collection<int, PrevalidationRequestMissedRecord>
     */
    protected function assertPrevalidationRequestMissingRecordsState(
        PrevalidationRequest $prevalidationRequest,
    ): Collection {
        $missedRecords = $prevalidationRequest->missed_records()->get();

        $this->assertNull($prevalidationRequest->prevalidation);
        $this->assertNotEmpty($missedRecords);
        $this->assertEquals(PrevalidationRequest::STATE_MISSING_RECORDS, $prevalidationRequest->state);
        $this->assertFalse($prevalidationRequest->missing_records_approved);

        return $missedRecords;
    }

    /**
     * @param Organization $organization
     * @param Fund $fund
     * @param bool $includeRequiredIncomeGroup
     * @return array{prefillKey: string, manualKey: string}
     */
    protected function makeDefaultPrevalidationRequestCsvCriteria(
        Organization $organization,
        Fund $fund,
        bool $includeRequiredIncomeGroup = false,
    ): array {
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

        if ($includeRequiredIncomeGroup) {
            $criteria = [
                ...$criteria,
                ...$this->makePrevalidationRequestCsvIncomeGroupCriteria($organization, $fund),
            ];
        }

        $this->makeFundCriteria($fund, $criteria);

        return compact('prefillKey', 'manualKey');
    }

    /**
     * @param Organization $organization
     * @param Fund $fund
     * @return array
     */
    protected function makePrevalidationRequestCsvIncomeGroupCriteria(Organization $organization, Fund $fund): array
    {
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

        return [[
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
    }

    /**
     * @param Fund $fund
     * @param array $data
     * @return PrevalidationRequest
     */
    protected function assertPrevalidationRequestCreated(Fund $fund, array $data): PrevalidationRequest
    {
        $request = PrevalidationRequest::where('fund_id', $fund->id)
            ->whereHas('records', function (Builder $builder) use ($data) {
                $builder->where('record_type_key', 'uid');
                $builder->where('value', $data['uid']);
            })
            ->first();

        $this->assertNotNull($request);
        $this->assertRecordsEquals($request, $data);

        return $request;
    }

    /**
     * @param PrevalidationRequest $request
     * @param array $data
     * @return void
     */
    protected function assertRecordsEquals(PrevalidationRequest $request, array $data): void
    {
        $records = $request->records;

        foreach ($data as $field => $value) {
            $record = $records->first(fn (PrevalidationRequestRecord $record) => $record->record_type_key === $field);
            $this->assertNotNull($record);
            $this->assertEquals($value, $record->value);
        }
    }
}
