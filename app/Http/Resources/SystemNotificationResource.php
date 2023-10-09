<?php

namespace App\Http\Resources;

use App\Models\Implementation;
use App\Models\SystemNotification;
use App\Models\SystemNotificationConfig;
use App\Notifications\BaseNotification;
use Illuminate\Database\Eloquent\Collection;

/**
 * @property SystemNotification $resource
 */
class SystemNotificationResource extends BaseJsonResource
{
    public const LOAD = [
        'templates',
        'system_notification_configs',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Implementation $implementation */
        $systemNotification = $this->resource;
        $templates = $systemNotification->templates;

        $implementation = $request->implementation ?? null;
        $formal = !$implementation->informal_communication;

        /** @var SystemNotificationConfig|null $config */
        $configs = $systemNotification->system_notification_configs;
        $config = $configs->where('implementation_id', $implementation->id)->first();

        $generalTemplates = $this->getTemplates($templates, Implementation::general(), $formal);
        $implementationTemplates = $this->getTemplates($templates, $implementation, $formal);

        return array_merge($systemNotification->only([
            'id', 'key', 'optional', 'editable', 'group', 'order',
        ]), [
            'enable_all' => $config->enable_all ?? true,
            'enable_mail' => $config->enable_mail ?? true,
            'enable_push' => $config->enable_push ?? true,
            'enable_database' => $config->enable_database ?? true,
            'variables' => BaseNotification::getVariables($systemNotification->key),
            'channels' => $systemNotification->channels(),
            'templates' => NotificationTemplateResource::collection($implementationTemplates),
            'templates_default' => NotificationTemplateResource::collection($generalTemplates),
        ]);
    }

    /**
     * @param Collection $templates
     * @param Implementation $implementation
     * @param bool $formalCommunication
     * @return Collection
     */
    public function getTemplates(
        Collection $templates,
        Implementation $implementation,
        bool $formalCommunication
    ): Collection {
        $templates = $templates->where('implementation_id', $implementation->id);
        $templates = $templates->where('formal', $formalCommunication);

        if ($implementation->isGeneral() || !$implementation->allow_per_fund_notification_templates) {
            $templates->whereNull('fund_id');
        }

        return $templates->values();
    }
}
