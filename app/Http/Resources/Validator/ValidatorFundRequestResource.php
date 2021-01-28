<?php

namespace App\Http\Resources\Validator;

use App\Http\Resources\EmployeeResource;
use App\Http\Resources\FileResource;
use App\Http\Resources\FundCriterionResource;
use App\Http\Resources\FundRequestClarificationResource;
use App\Models\Employee;
use App\Models\FundRequest;
use App\Models\FundRequestRecord;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class FundRequestResource
 * @property FundRequest $resource
 * @package App\Http\Resources
 */
class ValidatorFundRequestResource extends Resource
{
    /**
     * @var string[]
     */
    public static $load = [
        'records.employee.organization',
        'records.files',
        'records.fund_request_clarifications',
        'fund.criteria.fund_criterion_validators.external_validator',
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

        return array_merge(array_only($fundRequest->toArray(), [
            'id', 'state', 'fund_id', 'note', 'created_at', 'updated_at'
        ]), [
            'fund' => array_merge($fundRequest->fund->only([
                'id', 'name', 'description', 'organization_id', 'state', 'notification_amount',
                'tags', 'type',
            ]), [
                'criteria' => FundCriterionResource::collection($fundRequest->fund->criteria),
            ]),
            'bsn' => $recordRepo->bsnByAddress($fundRequest->identity_address),
            'created_at_locale' => format_datetime_locale($this->resource->created_at),
            'updated_at_locale' => format_datetime_locale($this->resource->updated_at),
            'records' => $this->getRecordsData($request, $fundRequest),
        ]);
    }

    public function getRecordsData(Request $request, FundRequest $fundRequest): array {
        /** @var Organization $organization */
        $organization = $request->route('organization') or abort(403);
        $employee = $organization->findEmployee(auth_address()) or abort(403);

        $availableRecords = $fundRequest->recordsWhereCanValidateQuery(
            auth_address(),
            $employee->id
        )->pluck('fund_request_records.id')->toArray();

        $records = [];

        foreach ($fundRequest->records as $record) {
            $records[] = static::recordToArray($record, $employee, in_array(
                $record->id, $availableRecords
            ));
        }

        return $records;
    }

    /**
     * Transform the resource into an array.
     *
     * @param FundRequestRecord $record
     * @param Employee|null $employee
     * @param bool $isValueReadable
     * @return array
     */
    static function recordToArray(
        FundRequestRecord $record,
        Employee $employee,
        bool $isValueReadable
    ): array {
        $is_value_readable = $isValueReadable;
        $is_assigned = $record->employee_id === $employee->id;
        $is_assignable = $is_value_readable && !$is_assigned;

        $is_visible = $is_assignable || $is_assigned || $is_value_readable;
        $recordTypes = collect(record_types_cached())->keyBy('key');

        return array_merge($record->only(array_merge([
            'id', 'state', 'record_type_key', 'fund_request_id',
            'created_at', 'updated_at', 'employee_id', 'note',
        ], $is_visible ? [
            'value'
        ] : [])), array_merge($is_assigned ? [
            'files' => FileResource::collection($record->files),
            'clarifications' => FundRequestClarificationResource::collection(
                $record->fund_request_clarifications
            ),
        ] : [
            'files' => [],
            'clarifications' => [],
        ], [
            'employee' => new EmployeeResource($record->employee),
            'record_type' => $recordTypes[$record->record_type_key],
            'created_at_locale' => format_datetime_locale($record->created_at),
            'updated_at_locale' => format_datetime_locale($record->updated_at),
        ], compact('is_assignable', 'is_assigned', 'is_visible')));
    }
}
