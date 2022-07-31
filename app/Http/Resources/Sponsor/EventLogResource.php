<?php

namespace App\Http\Resources\Sponsor;

use App\Http\Resources\BaseJsonResource;
use App\Services\EventLogService\Models\EventLog;

/**
 * @property EventLog $resource
 */
class EventLogResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param $request
     * @return array
     */
    public function toArray($request): array
    {
        $resource = $this->resource;

        return array_merge($resource->only([
            'id', 'event', 'data', 'identity_address', 'original',
        ]), [
            'loggable' => $resource->loggable_locale,
            'event' => $resource->getEventLocale(EventLog::TRANSLATION_DASHBOARD),
            'identity_email' => $resource->identity?->email,
            'note' => $resource->getNote(),
            'created_at' => $resource->created_at?->format('Y-m-d H:i:s'),
            'created_at_locale' => format_datetime_locale($resource->created_at),
        ]);
    }
}