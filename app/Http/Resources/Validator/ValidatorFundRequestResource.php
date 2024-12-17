<?php

namespace App\Http\Resources\Validator;

use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\FileResource;
use App\Http\Resources\FundAmountPresetResource;
use App\Http\Resources\FundCriterionResource;
use App\Http\Resources\FundFormulaProductResource;
use App\Http\Resources\FundFormulaResource;
use App\Http\Resources\FundRequestClarificationResource;
use App\Http\Resources\TagResource;
use App\Models\Employee;
use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\FundRequestRecord;
use App\Models\Organization;
use App\Models\Permission;
use App\Scopes\Builders\EmployeeQuery;
use App\Scopes\Builders\FundRequestQuery;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * @property FundRequest $resource
 */
class ValidatorFundRequestResource extends BaseJsonResource
{
    /**
     * @var string[]
     */
    public const array LOAD = [
        'records.files.preview.presets',
        'records.record_type.translations',
        'employee.organization',
        'employee.roles.translations',
        'employee.roles.permissions',
        'records.fund_request_clarifications.files.preview.presets',
        'records.fund_request_clarifications.fund_request_record.record_type.translations',
        'identity.primary_email',
        'fund.criteria.record_type.translation',
        'fund.tags',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $fundRequest = $this->resource;
        $baseFormRequest = BaseFormRequest::createFrom($request);

        /** @var Organization $organization */
        $organization = $request->route('organization') or abort(403);
        $allowedEmployees = $this->getAllowedRequestEmployeesQuery($baseFormRequest, $organization)->get();

        /** @var Organization $organization */
        $organization = $request->route('organization') or abort(403);
        $bsn_enabled = $organization->bsn_enabled;

        return array_merge($fundRequest->only([
            'id', 'state', 'fund_id', 'note', 'lead_time_days', 'lead_time_locale',
            'contact_information', 'state_locale', 'employee_id',
        ]), [
            'bsn' => $bsn_enabled ? $fundRequest->identity->bsn : null,
            'fund' => $this->fundDetails($fundRequest->fund),
            'email' => $fundRequest->identity->email,
            'records' => $this->getRecordsDetails($baseFormRequest, $organization, $fundRequest),
            'replaced' => $this->isReplaced($fundRequest),
            'employee' => new EmployeeResource($fundRequest->employee),
            'allowed_employees' => $allowedEmployees->map(fn(Employee $employee) => [
                ...$employee->only([
                    'id', 'organization_id', 'identity_address',
                ]),
                'email' => $employee->identity?->email,
            ])->toArray(),
        ], $this->timestamps($fundRequest, 'created_at', 'updated_at', 'resolved_at'));
    }

    /**
     * @param BaseFormRequest $request
     * @param Organization $organization
     * @return Builder|Relation
     */
    protected function getAllowedRequestEmployeesQuery(
        BaseFormRequest $request,
        Organization $organization,
    ): Relation|Builder {
        $employeesQuery = $organization->employees();

        $isManagerQuery = $organization
            ->employeesWithPermissionsQuery(Permission::MANAGE_VALIDATORS)
            ->where('identity_address', $request->auth_address())
            ->exists();

        if (!$isManagerQuery) {
            $employeesQuery->where('identity_address', $request->auth_address());
        }

        return EmployeeQuery::whereHasPermissionFilter($employeesQuery, [
            Permission::VALIDATE_RECORDS,
        ]);
    }

    /**
     * @param Fund $fund
     * @return array
     */
    protected function fundDetails(Fund $fund): array
    {
        return [
            ...$fund->only([
                'id', 'name', 'description', 'organization_id', 'state', 'type',
            ]),
            ...$fund->fund_config->only([
                'allow_custom_amounts', 'allow_preset_amounts',
                'allow_custom_amounts_validator', 'allow_preset_amounts_validator',
                'custom_amount_min', 'custom_amount_max',
            ]),
            'tags' => TagResource::collection($fund->tags),
            'criteria' => FundCriterionResource::collection($fund->criteria),
            'amount_presets' => FundAmountPresetResource::collection(
                $fund->fund_config?->allow_preset_amounts_validator ? $fund->amount_presets : [],
            ),
            'has_person_bsn_api' => $fund->hasIConnectApiOin(),
            'formulas' => FundFormulaResource::collection($fund->fund_formulas),
            'formula_products' => FundFormulaProductResource::collection($fund->fund_formula_products),
        ];
    }

    /**
     * @param FundRequest $request
     * @return bool
     */
    protected function isReplaced(FundRequest $request): bool
    {
        return $request->isDisregarded() && FundRequestQuery::wherePendingOrApprovedAndVoucherIsActive(
            $request->fund->fund_requests()->where('id', '!=', $request->id),
            $request->id,
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
        FundRequest $fundRequest,
    ): array {
        $employee = $request->employee($organization) or abort(403);
        $bsnFields = ['bsn', 'partner_bsn', 'bsn_hash', 'partner_bsn_hash'];

        return $fundRequest->records->filter(function(FundRequestRecord $record) use ($organization, $bsnFields) {
            return $organization->bsn_enabled || !in_array($record->record_type_key, $bsnFields, true);
        })->map(function(FundRequestRecord $record) use ($employee) {
            return static::recordToArray($record, $employee);
        })->toArray();
    }

    /**
     * Transform the resource into an array.
     *
     * @param FundRequestRecord $record
     * @param Employee $employee
     * @return array
     */
    static function recordToArray(
        FundRequestRecord $record,
        Employee $employee,
    ): array {
        $is_assigned = $record->fund_request->employee_id === $employee->id;
        $is_assignable = !$is_assigned && !$record->fund_request->employee_id && $record->fund_request->isPending();

        $baseFields = array_merge($record->only([
            'id', 'record_type_key', 'fund_request_id', 'fund_criterion_id',
        ]), [
            'value' => $is_assignable || $is_assigned ? $record->value : null,
        ]);

        $relations = [
            'files' => FileResource::collection($record->files),
            'history' => $is_assignable || $is_assigned ? self::getHistory($record)->values() : [],
            'clarifications' => FundRequestClarificationResource::collection($record->fund_request_clarifications),
        ];

        return array_merge($baseFields, $relations, [
            'record_type' => [
                ...$record->record_type->only(['name', 'key', 'type']),
                'options' => $record->record_type->getOptions(),
            ],

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
