<?php

namespace App\Http\Resources\Validator;

use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\FileResource;
use App\Http\Resources\FundCriterionResource;
use App\Http\Resources\FundRequestClarificationResource;
use App\Http\Resources\TagResource;
use App\Models\Employee;
use App\Models\FundRequest;
use App\Models\FundRequestRecord;
use App\Models\Organization;
use App\Scopes\Builders\EmployeeQuery;
use App\Scopes\Builders\FundRequestQuery;
use App\Scopes\Builders\FundRequestRecordQuery;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

/**
 * @property FundRequest $resource
 */
class ValidatorFundRequestResource extends BaseJsonResource
{
    /**
     * @var string[]
     */
    public const LOAD = [
        'records.files.preview.presets',
        'records.record_type.translations',
        'records.employee.organization',
        'records.employee.roles.translations',
        'records.employee.roles.permissions',
        'records.fund_request_clarifications.files.preview.presets',
        'records.fund_request_clarifications.fund_request_record.record_type.translations',
        'identity.primary_email',
        'fund.criteria.record_type.translation',
        'fund.criteria.fund_criterion_validators.external_validator',
        'fund.tags',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $fundRequest = $this->resource;
        $baseFormRequest = BaseFormRequest::createFrom($request);

        /** @var Organization $organization */
        $organization = $request->route('organization') or abort(403);
        $allowedEmployees = $this->getAllowedRequestEmployeesQuery($baseFormRequest, $fundRequest, $organization)->get();

        /** @var Organization $organization */
        $organization = $request->route('organization') or abort(403);
        $bsn_enabled = $organization->bsn_enabled;

        return array_merge($fundRequest->only([
            'id', 'state', 'fund_id', 'note', 'lead_time_days', 'lead_time_locale',
            'contact_information', 'state_locale',
        ]), [
            'bsn' => $bsn_enabled ? $fundRequest->identity->bsn : null,
            'fund' => $this->fundDetails($fundRequest),
            'email' => $fundRequest->identity->email,
            'records' => $this->getRecordsDetails($baseFormRequest, $organization, $fundRequest),
            'replaced' => $this->isReplaced($fundRequest),
            'allowed_employees' => $allowedEmployees->map(fn(Employee $employee) => $employee->only([
                'id', 'organization_id', 'identity_address',
            ]))->toArray(),
        ], $this->timestamps($fundRequest, 'created_at', 'updated_at', 'resolved_at'));
    }

    /**
     * @param BaseFormRequest $request
     * @param FundRequest $fundRequest
     * @param Organization $organization
     * @return Builder|Relation
     */
    protected function getAllowedRequestEmployeesQuery(
        BaseFormRequest $request,
        FundRequest $fundRequest,
        Organization $organization
    ): Relation|Builder {
        $recordsQuery = $fundRequest->records_pending()->whereNull('employee_id');
        $employeesQuery = $organization->employees();
        $isSponsorOrganization = $organization->id === $fundRequest->fund->organization_id;

        $isManagerQuery = $organization->employeesWithPermissionsQuery('manage_validators')->where([
            'identity_address' => $request->auth_address(),
        ]);

        if (!$isSponsorOrganization && !$isManagerQuery->exists()) {
            $employeesQuery->where('identity_address', $request->auth_address());
        }

        return EmployeeQuery::whereCanValidateRecords(
            $employeesQuery,
            $recordsQuery->select('fund_request_records.id')->getQuery()
        );
    }

    /**
     * @param FundRequest $request
     * @return array
     */
    protected function fundDetails(FundRequest $request): array
    {
        return array_merge($request->fund->only([
            'id', 'name', 'description', 'organization_id', 'state', 'type',
        ]), [
            'criteria' => FundCriterionResource::collection($request->fund->criteria),
            'tags' => TagResource::collection($request->fund->tags),
            'has_person_bsn_api' => $request->fund->hasIConnectApiOin(),
        ]);
    }

    /**
     * @param FundRequest $request
     * @return bool
     */
    protected function isReplaced(FundRequest $request): bool
    {
        return $request->isDisregarded() && FundRequestQuery::wherePendingOrApprovedAndVoucherIsActive(
            $request->fund->fund_requests()->where('id', '!=', $request->id),
            $request->identity_address
        )->exists();
    }

    /**
     * @param BaseFormRequest $request
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @return array
     */
    public function getRecordsDetails(
        BaseFormRequest $request,
        Organization $organization,
        FundRequest $fundRequest
    ): array {
        $employee = $request->employee($organization) or abort(403);
        $bsnFields = ['bsn', 'partner_bsn', 'bsn_hash', 'partner_bsn_hash'];

        $availableRecords = FundRequestRecordQuery::whereEmployeeCanBeValidator(
            $fundRequest->records(), $employee,
        )->pluck('fund_request_records.id');

        return $fundRequest->records->filter(function(FundRequestRecord $record) use ($organization, $bsnFields) {
            return $organization->bsn_enabled || !in_array($record->record_type_key, $bsnFields, true);
        })->map(function(FundRequestRecord $record) use ($employee, $availableRecords) {
            return static::recordToArray($record, $employee, $availableRecords->search($record->id) !== false);
        })->toArray();
    }

    /**
     * Transform the resource into an array.
     *
     * @param FundRequestRecord $record
     * @param Employee $employee
     * @param bool $isRecordAssignable
     * @return array
     */
    static function recordToArray(
        FundRequestRecord $record,
        Employee $employee,
        bool $isRecordAssignable,
    ): array {
        $is_assigned = $record->employee_id === $employee->id;
        $is_assignable = $isRecordAssignable && !$is_assigned && !$record->employee && $record->isPending();

        $baseFields = array_merge($record->only([
            'id', 'state', 'record_type_key', 'fund_request_id', 'employee_id', 'note', 'fund_criterion_id',
        ]), [
            'value' => $is_assignable || $is_assigned ? $record->value : null,
        ]);

        $relations = [
            'files' => FileResource::collection($record->files),
            'history' => $is_assignable || $is_assigned ? self::getHistory($record)->values() : [],
            'clarifications' => FundRequestClarificationResource::collection($record->fund_request_clarifications),
        ];

        return array_merge($baseFields, $relations, [
            'employee' => new EmployeeResource($record->employee),
            'record_type' => [
                ...$record->record_type->only(['name', 'key', 'type']),
                'options' => $record->record_type->getOptions(),
            ],
            'is_assigned' => $is_assigned,
            'is_assignable' => $is_assignable,
        ], static::staticTimestamps($record, 'created_at', 'updated_at'));
    }

    /**
     * @param FundRequestRecord $record
     * @return \Illuminate\Support\Collection
     */
    static function getHistory(FundRequestRecord $record): Collection
    {
        return $record->historyLogs()->map(fn (EventLog $eventLog) => array_merge([
            'id' => $eventLog->id,
            'new_value' => $eventLog->data['fund_request_record_value'] ?? '',
            'old_value' => $eventLog->data['fund_request_record_previous_value'] ?? '',
            'employee_email' => $eventLog->data['employee_email'] ?? '',
        ], self::makeTimestampsStatic($eventLog->only('created_at'))));
    }
}
