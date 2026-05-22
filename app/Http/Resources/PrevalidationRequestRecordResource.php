<?php

namespace App\Http\Resources;

use App\Models\PrevalidationRequestRecord;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * @property PrevalidationRequestRecord $resource
 */
class PrevalidationRequestRecordResource extends BaseJsonResource
{
    public const array LOAD = [
        'logs',
        'record_type.translation',
        'record_type.record_type_options.translations',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            ...$this->resource->only([
                'id', 'record_type_key', 'prevalidation_request_id', 'value', 'source',
            ]),
            'record_type' => [
                ...$this->resource->record_type->only([
                    'key', 'name', 'type',
                ]),
                'name' => $this->resource->record_type?->name ?: $this->resource->record_type?->key,
                'options' => $this->resource->record_type?->getOptions(),
            ],
            'history' => $this->getHistory()->values(),
            $this->makeTimestamps($this->resource->only([
                'created_at', 'updated_at',
            ])),
        ];
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    protected function getHistory(): Collection
    {
        return $this->resource->historyLogs()->map(fn (EventLog $eventLog) => [
            'id' => $eventLog->id,
            'new_value' => $eventLog->data['prevalidation_request_record_value'] ?? '',
            'old_value' => $eventLog->data['prevalidation_request_record_previous_value'] ?? '',
            'employee_email' => $eventLog->data['employee_email'] ?? '',
            ...self::makeTimestampsStatic($eventLog->only([
                'created_at',
            ])),
        ]);
    }
}
