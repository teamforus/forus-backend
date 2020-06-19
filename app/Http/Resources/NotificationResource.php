<?php

namespace App\Http\Resources;

use App\Services\EventLogService\Models\EventLog;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\Forus\Identity\Models\DatabaseNotification;

/**
 * Class NotificationResource
 * @property DatabaseNotification $resource
 * @package App\Http\Resources
 */
class NotificationResource extends JsonResource
{
    public static function collectionWithMeta(
        $resource,
        array $meta
    ): AnonymousResourceCollection{
        return parent::collection($resource)->additional($meta);
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $key = $this->resource->data['key'];
        $event = EventLog::find($this->resource->data['event_id']);
        $meta = $event->data;

        return [
            'id' => $this->resource->id,
            'type' => $key,
            // contains sensitive information
            // 'meta' => $meta,
            'title' => trans("notifications/{$key}.title", $meta),
            'description' => trans("notifications/{$key}.description", $meta),
            'seen' => $this->resource->read_at != null,
            'created_at' => $this->resource->created_at->format('Y-m-d H:i:s'),
            'created_at_locale' => format_datetime_locale($this->resource->created_at),
        ];
    }
}
