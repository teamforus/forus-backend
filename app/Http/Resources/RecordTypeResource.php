<?php

namespace App\Http\Resources;

use App\Models\RecordType;
use Illuminate\Http\Request;

/**
 * @property-read RecordType $resource
 */
class RecordTypeResource extends BaseJsonResource
{
    public static $wrap = false;

    const array LOAD = [
        'translations',
        'record_type_options',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $recordType = $this->resource;

        return [
            ...$recordType->only([
                'key', 'type', 'system', 'criteria', 'control_type',
            ]),
            'name' => $recordType->name ?: $recordType->key,
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
                    ][$operator] ?? ''
            ], $recordType->getOperators()),
            'options' => $recordType->getOptions(),
        ];
    }
}