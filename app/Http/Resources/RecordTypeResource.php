<?php

namespace App\Http\Resources;

use App\Models\RecordType;

/**
 * @property-read RecordType $resource
 */
class RecordTypeResource extends BaseJsonResource
{
    public static $wrap = false;

    const LOAD = [
        'record_type_options',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $recordType = $this->resource;

        return array_merge($recordType->only('key', 'type', 'system'), [
            'name' => $recordType->name ?: $recordType->key,
            'validations' => $recordType->getValidations(),
            'operators' => array_map(fn ($operator) => [
                'key' => $operator,
                'name' => [
                    '*' => 'any value',
                    '=' => 'gelijk aan',
                    '<' => 'is kleiner dan',
                    '>' => 'is groter dan',
                    '<=' => 'is less or equal',
                    '>=' => 'is more or equal',
                ][$operator] ?? ''
            ], $recordType->getOperators()),
            'options' => $recordType->getOptions(),
        ]);
    }
}