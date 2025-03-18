<?php

namespace App\Http\Resources;

use App\Models\Implementation;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Http\Request;
use Throwable;

/**
 * @property Notification $resource
 */
class NotificationResource extends BaseJsonResource
{
    public const array LOAD = [
        'event',
        'system_notification.templates',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @throws Throwable
     * @return array
     */
    public function toArray(Request $request): array
    {
        $event = $this->resource->event;
        $template = $this->getTemplate($event);
        $templateData = array_map(fn ($item) => is_array($item) ? '' : $item, $event->data);

        return array_merge([
            'id' => $this->resource->id,
            'type' => $this->resource->key,
            'seen' => $this->resource->read_at != null,
        ], $template ? [
            'title' => str_var_replace($template->title, $templateData),
            'description' => str_var_replace($template->content, $templateData),
        ] : [], $this->timestamps($this->resource, 'created_at'));
    }

    /**
     * @param EventLog $event
     * @throws Throwable
     * @return NotificationTemplate|null
     */
    public function getTemplate(EventLog $event): ?NotificationTemplate
    {
        try {
            return $this->resource->system_notification->findTemplate(
                Implementation::findAndMemo($event->data['implementation_key'] ?? Implementation::KEY_GENERAL),
                $event->data['fund_id'] ?? null,
                'database',
            );
        } catch (Throwable $e) {
            if ($logger = logger()) {
                $logger->error(sprintf('Could not find template for "%s" notification.', $this->resource->key));
            }

            throw $e;
        }
    }
}
