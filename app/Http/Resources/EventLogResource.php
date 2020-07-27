<?php

namespace App\Http\Resources;

use App\Services\EventLogService\Models\EventLog;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property EventLog $resource
 * Class EventLogResource
 * @package App\Http\Resources
 */
class EventLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $eventLog = $this->resource;
        $eventMeta = $eventLog->meta->pluck('value', 'key')->toArray();

        $transKey = sprintf(
            'logs/notifications.%s.%s.',
            $eventLog->loggable_type,
            $eventLog->event
        );

        return array_merge($eventLog->toArray(), [
            'created_at_locale' => format_datetime_locale($eventLog->created_at),
            'meta' => $eventLog->meta->pluck('value', 'key'),
            'title' => trans($transKey . 'title', $eventMeta),
            'description' => trans($transKey . 'description', $eventMeta),
        ]);
    }
}
