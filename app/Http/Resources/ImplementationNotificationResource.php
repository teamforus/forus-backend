<?php

namespace App\Http\Resources;

use App\Models\NotificationTemplate;
use App\Services\Forus\Notification\Repositories\Data\NotificationType;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property NotificationType $resource
 */
class ImplementationNotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $notificationType = $this->resource;
        $templates = NotificationTemplate::where('key', $notificationType->getKey())->get();

        return [
            'key' => $notificationType->getKey(),
            'scope' => $notificationType->getScope(),
            'channels' => $notificationType->getChannels(),
            'templates' => NotificationTemplateResource::collection($templates),
        ];
    }
}
