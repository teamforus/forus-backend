<?php

namespace App\Http\Resources;

use App\Models\Implementation;
use App\Models\NotificationTemplate;
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
        $prefixTitle = "notifications/$key.title";
        $prefixDescription = "notifications/$key.description";

        $template = $this->getTemplate();

        return array_merge([
            'id' => $this->resource->id,
            'type' => $key,
            'seen' => $this->resource->read_at != null,
            'created_at' => $this->resource->created_at->format('Y-m-d H:i:s'),
            'created_at_locale' => format_datetime_locale($this->resource->created_at),
        ], $template ? [
            'title' => str_var_replace($template->title, $event->data),
            'description' => str_var_replace($template->content, $event->data),
        ] : [
            'title' => $this->getTranslation($prefixTitle, $suffix, $event->data),
            'description' => $this->getTranslation($prefixDescription, $suffix, $event->data),
        ]);
    }

    /**
     * @return NotificationTemplate|null
     */
    public function getTemplate(): ?NotificationTemplate
    {
        $template = NotificationTemplate::where([
            'key' => $this->resource->data['key'],
            'type' => 'database',
            'implementation_id' => Implementation::general()->id,
        ])->first();

        return NotificationTemplate::where([
            'key' => $this->resource->data['key'],
            'type' => 'database',
            'implementation_id' => Implementation::active()->id,
        ])->first() ?: $template;
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
