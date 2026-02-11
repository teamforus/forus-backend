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
use App\Models\FundRequestRecordGroup;
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
        'records.fund_request',
        'records.logs',
        'records.record_type.translations',
        'records.record_type.record_type_options.translations',
        'identity.primary_email',
        'fund.fund_config',
    ];

    public const array LOAD_NESTED = [
        'records.files' => FileResource::class,
        'records.fund_request_clarifications' => FundRequestClarificationResource::class,
        'employee' => EmployeeResource::class,
        'fund.tags' => TagResource::class,
        'fund.criteria' => FundCriterionResource::class,
        'fund.amount_presets' => FundAmountPresetResource::class,
        'fund.fund_formulas' => FundFormulaResource::class,
        'fund.fund_formula_products' => FundFormulaProductResource::class,
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $fundRequest = $this->resource;
        $baseFormRequest = BaseFormRequest::createFrom($request);

        /** @var Organization $organization */
        $organization = $baseFormRequest->route('organization') or abort(403);
        $allowedEmployees = $this->getAllowedRequestEmployeesQuery($baseFormRequest, $organization)->get();
        $bsn_enabled = $organization->bsn_enabled;
        $visibleRecords = $this->getVisibleRecords($organization, $fundRequest);
        $employee = $baseFormRequest->employee($organization) or abort(403);

        return [
            ...$fundRequest->only([
                'id', 'state', 'fund_id', 'note', 'lead_time_days', 'lead_time_locale',
                'contact_information', 'state_locale', 'employee_id', 'identity_id',
            ]),
            'bsn' => $bsn_enabled ? $fundRequest->identity->bsn : null,
            'fund' => $this->fundDetails($fundRequest->fund),
            'email' => $fundRequest->identity->email,
            'records' => $visibleRecords
                ->map(fn (FundRequestRecord $record) => static::recordToArray($record, $employee))
                ->toArray(),
            'record_groups' => $this->getRecordGroups($fundRequest, $visibleRecords),
            'replaced' => $this->isReplaced($fundRequest),
            'employee' => new EmployeeResource($fundRequest->employee),
            'allowed_employees' => $allowedEmployees->map(fn (Employee $employee) => [
                ...$employee->only([
                    'id', 'organization_id', 'identity_address',
                ]),
                'email' => $employee->identity?->email,
            ])->toArray(),
            ...$this->makeTimestamps($fundRequest->only([
                'created_at', 'updated_at', 'resolved_at',
            ])),
        ];
    }

    /**
     * Transform the resource into an array.
     *
     * @param FundRequestRecord $record
     * @param Employee $employee
     * @return array
     */
    public static function recordToArray(
        FundRequestRecord $record,
        Employee $employee,
    ): array {
        $is_assigned = $record->fund_request->employee_id === $employee->id;
        $is_assignable = !$is_assigned && !$record->fund_request->employee_id && $record->fund_request->isPending();

        $baseFields = array_merge($record->only([
            'id', 'record_type_key', 'fund_request_id', 'fund_criterion_id', 'source',
        ]), [
            'value' => $is_assignable || $is_assigned ? $record->value : null,
        ]);

        $relations = [
            'files' => FileResource::collection($record->files),
            'history' => $is_assignable || $is_assigned ? static::getHistory($record)->values() : [],
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
    public static function getHistory(FundRequestRecord $record): Collection
    {
        return $record->historyLogs()->map(fn (EventLog $eventLog) => [
            'id' => $eventLog->id,
            'new_value' => $eventLog->data['fund_request_record_value'] ?? '',
            'old_value' => $eventLog->data['fund_request_record_previous_value'] ?? '',
            'employee_email' => $eventLog->data['employee_email'] ?? '',
            ...self::makeTimestampsStatic($eventLog->only([
                'created_at',
            ])),
        ]);
    }

    /**
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @return Collection
     */
    protected function getVisibleRecords(Organization $organization, FundRequest $fundRequest): Collection
    {
        $bsnFields = ['bsn', 'partner_bsn'];

        return $fundRequest->records
            ->filter(function (FundRequestRecord $record) use ($organization, $bsnFields) {
                return $organization->bsn_enabled || !in_array($record->record_type_key, $bsnFields, true);
            })
            ->values();
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
        $employeesQuery = $organization->employees()->with('identity');

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
     * @param FundRequest $fundRequest
     * @param Collection $records
     * @return array
     */
    protected function getRecordGroups(FundRequest $fundRequest, Collection $records): array
    {
        $groups = FundRequestRecordGroup::getCachedList()
            ->filter(function (FundRequestRecordGroup $group) use ($fundRequest) {
                return
                    // global scope
                    (!$group->organization_id && !$group->fund_id) ||
                    // organization scope
                    ($group->organization_id === $fundRequest->fund->organization_id && !$group->fund_id) ||
                    // organization and fund scope
                    ($group->organization_id === $fundRequest->fund->organization_id && $group->fund_id === $fundRequest->fund_id);
            })
            ->values();

        $groupsPriority = $groups
            ->sortBy(fn (FundRequestRecordGroup $group) => $group->fund_id ? 0 : ($group->organization_id ? 1 : 2))
            ->values();

        $groupRecordTypes = $groupsPriority
            ->mapWithKeys(fn (FundRequestRecordGroup $group) => [
                $group->id => $group->records->pluck('record_type_key')->values()->toArray(),
            ])
            ->toArray();

        $recordIdsByGroup = $groupsPriority
            ->pluck('id')
            ->mapWithKeys(fn (int $groupId) => [$groupId => []])
            ->toArray();

        $ungroupedRecordIds = [];

        foreach ($records as $record) {
            $assigned = false;

            foreach ($groupsPriority as $group) {
                if (in_array($record->record_type_key, $groupRecordTypes[$group->id] ?? [], true)) {
                    $recordIdsByGroup[$group->id][] = $record->id;
                    $assigned = true;
                    break;
                }
            }

            if (!$assigned) {
                $ungroupedRecordIds[] = $record->id;
            }
        }

        $recordGroups = $groups
            ->map(fn (FundRequestRecordGroup $group) => [
                ...$group->only([
                    'id', 'title', 'organization_id', 'fund_id', 'order',
                ]),
                'record_ids' => $recordIdsByGroup[$group->id] ?? [],
            ])
            ->filter(fn (array $group) => count($group['record_ids']) > 0)
            ->values()
            ->toArray();

        if (!empty($ungroupedRecordIds)) {
            $recordGroups[] = [
                'id' => 0,
                'title' => 'Overige gegevens',
                'organization_id' => null,
                'fund_id' => null,
                'order' => 999,
                'record_ids' => $ungroupedRecordIds,
            ];
        }

        return $recordGroups;
    }
}
