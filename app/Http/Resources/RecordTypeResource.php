<?php

namespace App\Http\Resources;

use App\Http\Requests\BaseFormRequest;
use App\Models\RecordType;
use Illuminate\Http\Request;

/**
 * @property-read RecordType $resource
 */
class RecordTypeResource extends BaseJsonResource
{
    public const array LOAD = [
        'translations',
        'record_type_options.translations',
        'profile_record_group.translations',
    ];

    public static $wrap = false;

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $baseRequest = BaseFormRequest::createFrom($request);
        $recordType = $this->resource;

        return [
            ...$recordType->only([
                'key', 'type', 'system', 'criteria', 'control_type', 'requester_imports',
                'profile_records', 'profile_requester_editable',
            ]),
            'name' => $recordType->name ?: $recordType->key,
            ...($baseRequest->isDashboard() ? [
                'organization_id' => $recordType->organization_id,
                'profile_sort_order' => $recordType->profile_sort_order,
                'profile_record_group' => $recordType->profile_record_group ?
                    new ProfileRecordGroupResource($recordType->profile_record_group) : null,
            ] : []),
            'validations' => $recordType->getValidations(),
            'operators' => array_map(fn ($operator) => [
                'key' => $operator,
                'name' => [
                    '*' => 'n.v.t.',
                    '=' => 'gelijk aan',
                    '<' => 'is kleiner dan',
                    '>' => 'is groter dan',
                    '<=' => 'is kleiner dan of gelijk aan',
                    '>=' => 'is groter dan of gelijk aan',
                ][$operator] ?? '',
            ], $recordType->getOperators()),
            'options' => $recordType->getOptions(),
        ];
    }
}
