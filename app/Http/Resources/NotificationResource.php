<?php

namespace App\Http\Resources;

use App\Models\Implementation;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\Forus\Identity\Models\DatabaseNotification;

/**
 * Class NotificationResource
 * @property DatabaseNotification $resource
 * @package App\Http\Resources
 */
class NotificationResource extends JsonResource
{
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
        $suffix = Implementation::active()->informal_communication ? '_informal' : '';
        $prefixTitle = "notifications/{$key}.title";
        $prefixDescription = "notifications/{$key}.description";

        return [
            'id' => $this->resource->id,
            'type' => $key,
            'title' => $this->getTranslation($prefixTitle, $suffix, $event->data),
            'description' => $this->getTranslation($prefixDescription, $suffix, $event->data),
            'seen' => $this->resource->read_at != null,
            'created_at' => $this->resource->created_at->format('Y-m-d H:i:s'),
            'created_at_locale' => format_datetime_locale($this->resource->created_at),
        ];
    }

    /**
     * @param string $prefix
     * @param string $suffix
     * @param array $data
     * @return string|\Symfony\Component\Translation\TranslatorInterface
     */
    function getTranslation(string $prefix, string $suffix, array $data)
    {
        return trans_fb($prefix . $suffix, trans($prefix, $data), $data);
    }
}
