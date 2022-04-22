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
use App\Scopes\Builders\FundRequestQuery;
use App\Services\Forus\Identity\Models\IdentityEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

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
        $criteria = FundCriterionResource::collection($fundRequest->fund->criteria);

        /** @var Organization $organization */
        $organization = $request->route('organization') or abort(403);
        $bsn_enabled = $organization->bsn_enabled;

        return array_merge($fundRequest->only([
            'id', 'state', 'fund_id', 'note', 'lead_time_days', 'lead_time_locale',
        ]), [
            'fund' => array_merge($fundRequest->fund->only([
                'id', 'name', 'description', 'organization_id', 'state', 'notification_amount', 'type',
            ]), [
                'criteria' => $criteria,
                'tags' => TagResource::collection($fundRequest->fund->tags),
            ]),
            'bsn' => $bsn_enabled ? $recordRepo->bsnByAddress($fundRequest->identity_address) : null,
            'email' => IdentityEmail::getEmailByAddress($fundRequest->identity_address),
            'records' => $this->getRecordsData($request, $fundRequest),
            'replaced' => $fundRequest->isDisregarded() && $this->isReplaced($fundRequest),
        ], $this->timestamps($fundRequest, 'created_at', 'updated_at', 'resolved_at'));
    }

    /**
     * @param FundRequest $fundRequest
     * @return bool
     */
    protected function isReplaced(FundRequest $fundRequest): bool
    {
        return $fundRequest->fund->fund_requests()->where(function(Builder $builder) use ($fundRequest) {
            FundRequestQuery::wherePendingOrApprovedAndVoucherIsActive($builder->where(function(Builder $builder) use ($fundRequest) {
                $builder->where('id', '!=', $fundRequest->id);
            }), $fundRequest->identity_address);
        })->where('id', '!=', $fundRequest->id)->exists();
    }

    /**
     * @param Request $request
     * @param FundRequest $fundRequest
     * @return array
     */
    public function getRecordsData(Request $request, FundRequest $fundRequest): array
    {
        /** @var Organization $organization */
        $organization = $request->route('organization') or abort(403);
        $employee = $organization->findEmployee(auth_address()) or abort(403);

        $availableRecords = $fundRequest->recordsWhereCanValidateQuery(
            auth_address(),
            $employee->id
        )->pluck('fund_request_records.id')->toArray();

        $records = [];
        $bsnFields = ['bsn', 'partner_bsn', 'bsn_hash', 'partner_bsn_hash'];

        foreach ($fundRequest->records as $record) {
            if ($organization->bsn_enabled || !in_array($record->record_type_key, $bsnFields, true)) {
                $records[] = static::recordToArray($record, $employee, in_array($record->id, $availableRecords));
            }
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
        $is_assignable = $is_value_readable && !$record->employee_id && $record->isPending();

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
