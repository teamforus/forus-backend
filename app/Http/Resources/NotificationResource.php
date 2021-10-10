<?php

namespace App\Http\Resources;

use App\Models\Implementation;
use App\Models\NotificationTemplate;
use App\Models\SystemNotification;
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
        $template = $this->getTemplate($event);

        return array_merge([
            'id' => $this->resource->id,
            'type' => $key,
            'seen' => $this->resource->read_at != null,
            'created_at' => $this->resource->created_at->format('Y-m-d H:i:s'),
            'created_at_locale' => format_datetime_locale($this->resource->created_at),
        ], $template ? [
            'title' => str_var_replace($template->title, $event->data),
            'description' => str_var_replace($template->content, $event->data),
        ] : []);
    }

    /**
     * @param EventLog $event
     * @return NotificationTemplate|null
     */
    public function getTemplate(EventLog $event): ?NotificationTemplate
    {;
        return SystemNotification::findTemplate(
            $this->resource->data['key'],
            'database',
            $event->data['implementation_key'] ?? Implementation::KEY_GENERAL
        );
    }

    /**
     * @param string $prefix
     * @param string $suffix
     * @param array $data
     * @return array|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Translation\Translator|string|null
     */
    function getTranslation(string $prefix, string $suffix, array $data)
    {
        return trans_fb($prefix . $suffix, trans($prefix, $data), $data);
    }
}
