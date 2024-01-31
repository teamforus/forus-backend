<?php

namespace App\Http\Resources;

use App\Models\Implementation;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use App\Models\SystemNotification;
use App\Services\EventLogService\Models\EventLog;
use Throwable;

/**
 * @property Notification $resource
 */
class NotificationResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     * @throws Throwable
     */
    public function toArray($request): array
    {
        $key = $this->resource->key;
        $event = $this->resource->event;
        $template = $this->getTemplate($event);

        return array_merge([
            'id' => $this->resource->id,
            'type' => $key,
            'seen' => $this->resource->read_at != null,
        ], $template ? [
            'title' => str_var_replace($template->title, $event->data),
            'description' => str_var_replace($template->content, $event->data),
        ] : [], $this->timestamps($this->resource, 'created_at'));
    }

    /**
     * @param EventLog $event
     * @return NotificationTemplate|null
     * @throws Throwable
     */
    public function getTemplate(EventLog $event): ?NotificationTemplate
    {
        try {
            return $this->resource->system_notification->findTemplateForDatabaseNotification(
                $event->data['implementation_key'] ?? Implementation::KEY_GENERAL,
                $event->data['fund_id'] ?? null,
            );
        } catch (Throwable $e) {
            if ($logger = logger()) {
                $logger->error(sprintf('Could not find template for "%s" notification.', $this->resource->key));
            }

            throw $e;
        }
    }
}
