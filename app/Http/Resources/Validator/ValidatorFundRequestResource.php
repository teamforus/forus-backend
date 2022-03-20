<?php

namespace App\Http\Resources\Validator;

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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Class FundRequestResource
 * @property FundRequest $resource
 * @package App\Http\Resources
 */
class ValidatorFundRequestResource extends BaseJsonResource
{
    /**
     * @var string[]
     */
    public const LOAD = [
        'records.files',
        'records.record_type',
        'records.employee.organization',
        'records.employee.roles.translations',
        'records.employee.roles.permissions',
        'records.fund_request_clarifications',
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
        $recordRepo = resolve('forus.services.record');
        $fundRequest = $this->resource;

        /** @var Organization $organization */
        $organization = $request->route('organization') or abort(403);
        $allowedEmployees = $this->getAllowedRequestEmployeesQuery($fundRequest, $organization)->get();

        /** @var Organization $organization */
        $organization = $request->route('organization') or abort(403);
        $bsn_enabled = $organization->bsn_enabled;

        return array_merge($fundRequest->only([
            'id', 'state', 'fund_id', 'note', 'lead_time_days', 'lead_time_locale',
        ]), [
            'bsn' => $bsn_enabled ? $recordRepo->bsnByAddress($fundRequest->identity_address) : null,
            'fund' => $this->fundDetails($fundRequest),
            'records' => $this->getRecordsDetails($organization, $fundRequest),
            'replaced' => $this->isReplaced($fundRequest),
            'allowed_employees' => $allowedEmployees->map(fn(Employee $employee) => $employee->only([
                'id', 'organization_id', 'identity_address',
            ]))->toArray(),
        ], $this->timestamps($fundRequest, 'created_at', 'updated_at', 'resolved_at'));
    }

    /**
     * @param FundRequest $fundRequest
     * @param Organization $organization
     * @return Builder|Relation
     */
    protected function getAllowedRequestEmployeesQuery(
        FundRequest $fundRequest,
        Organization $organization
    ) {
        $recordsQuery = $fundRequest->records_pending()->whereDoesntHave('employee');
        $employeesQuery = $organization->employees();
        $isSponsorOrganization = $organization->id === $fundRequest->fund->organization_id;

        $isManagerQuery = $organization->employeesWithPermissionsQuery('manage_validators')->where([
            'identity_address' => auth_address()
        ]);

        if (!$isSponsorOrganization && !$isManagerQuery->exists()) {
            $employeesQuery->where('identity_address', auth_address());
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
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @return array
     */
    public function getRecordsDetails(Organization $organization, FundRequest $fundRequest): array
    {
        $employee = $organization->findEmployee(auth_address()) or abort(403);
        $bsnFields = ['bsn', 'partner_bsn', 'bsn_hash', 'partner_bsn_hash'];
        $availableRecords = FundRequestRecordQuery::whereEmployeeCanBeValidator(
            $fundRequest->records(),
            $employee,
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
     * @param Employee|null $employee
     * @param bool $isRecordAssignable
     * @return array
     */
    static function recordToArray(
        FundRequestRecord $record,
        Employee $employee,
        bool $isRecordAssignable
    ): array {
        $is_assigned = $record->employee_id === $employee->id;
        $is_assignable = $isRecordAssignable && !$is_assigned && !$record->employee && $record->isPending();

        $baseFields = array_merge($record->only([
            'id', 'state', 'record_type_key', 'fund_request_id', 'employee_id', 'note',
        ]), [
            'value' => $is_assignable || $is_assigned ? $record->value : null,
        ]);

        $filesAndClarifications = $is_assigned ? [
            'files' => FileResource::collection($record->files),
            'clarifications' => FundRequestClarificationResource::collection($record->fund_request_clarifications),
        ] : [];

        return array_merge($baseFields, $filesAndClarifications, [
            'employee' => new EmployeeResource($record->employee),
            'record_type' => $record->record_type->only('id', 'key', 'type', 'system', 'name'),
            'is_assigned' => $is_assigned,
            'is_assignable' => $is_assignable,
        ], static::staticTimestamps($record, 'created_at', 'updated_at'));
    }
}
