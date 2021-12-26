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
use App\Services\IConnectApiService\IConnectApiService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class ValidatorFundRequestResource
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
     * @param \Illuminate\Http\Request $request
     * @return array
     * @throws \Exception
     */
    public function toArray($request): array
    {
        $recordRepo = resolve('forus.services.record');
        $fundRequest = $this->resource;
        $criteria = FundCriterionResource::collection($fundRequest->fund->criteria);
        $bsn = $recordRepo->bsnByAddress($fundRequest->identity_address);

        return array_merge($fundRequest->only([
            'id', 'state', 'fund_id', 'note', 'lead_time_days', 'lead_time_locale',
        ]), [
            'created_at' => $fundRequest->created_at ? $fundRequest->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $fundRequest->updated_at ? $fundRequest->updated_at->format('Y-m-d H:i:s') : null,
            'fund' => array_merge($fundRequest->fund->only([
                'id', 'name', 'description', 'organization_id', 'state', 'notification_amount',
                'tags', 'type',
            ]), compact('criteria')),
            'bsn' => $bsn,
            'created_at_locale' => format_datetime_locale($this->resource->created_at),
            'updated_at_locale' => format_datetime_locale($this->resource->updated_at),
            'resolved_at_locale' => format_datetime_locale($this->resource->resolved_at),
            'records' => $this->getRecordsData($request, $fundRequest, $bsn),
        ]);
    }

    /**
     * @throws \Exception
     */
    public function getRecordsData(
        Request $request, FundRequest $fundRequest, ?string $bsn
    ): array {
        /** @var Organization $organization */
        $organization = $request->route('organization') or abort(403);
        $employee = $organization->findEmployee(auth_address()) or abort(403);

        $availableRecords = $fundRequest->recordsWhereCanValidateQuery(
            auth_address(),
            $employee->id
        )->pluck('fund_request_records.id')->toArray();

        $records = [];
        $apiData = [];

        if ($organization->person_bsn_service_enabled && $bsn) {
            $apiData = $this->getApiDataByBSN($bsn);
        }

        foreach ($fundRequest->records as $record) {
            $records[] = static::recordToArray(
                $record, $employee, $apiData,
                in_array($record->id, $availableRecords)
            );
        }

        return $records;
    }

    /**
     * Transform the resource into an array.
     *
     * @param FundRequestRecord $record
     * @param Employee|null $employee
     * @param array $apiData
     * @param bool $isValueReadable
     * @return array
     */
    private static function recordToArray(
        FundRequestRecord $record,
        Employee $employee,
        array $apiData,
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
            'person_bsn_api_value' => $apiData[$record->record_type_key] ?? null,
            'record_type' => $recordTypes[$record->record_type_key],
            'created_at_locale' => format_datetime_locale($record->created_at),
            'updated_at_locale' => format_datetime_locale($record->updated_at),
        ], compact('is_assignable', 'is_assigned', 'is_visible')));
    }

    /**
     * @param string $bsn
     * @return array
     * @throws \Exception
     */
    private function getApiDataByBSN(string $bsn): array
    {
        /** @var IConnectApiService $iconnect */
        $iconnect = resolve('iconnect_api');

        $person = $iconnect->getPerson($bsn, [
            'parents', 'children', 'partners'
        ]);

        if ($person) {
            $array = $person->toArray();
            $map = [
                'first_name' => 'given_name',
                'last_name' => 'family_name',
                'birth_date' => 'birth_date',
                'bsn' => 'bsn',
                'gender' => 'gender',
                'residence' => 'address'
            ];

            $result = [];
            array_walk($array, static function($value, $key) use ($map, &$result) {
                if ($map[$key] ?? false) {
                    $result[$map[$key]] = is_array($value) ? implode(', ', $value) : $value;
                }
            });

            $result['partner_bsn'] = $array['partners'][0]['bsn'] ?? null;
            $result['children_nth'] = count($array['children']);

            return $result;
        }

        return [];
    }
}
