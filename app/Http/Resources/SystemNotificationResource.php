<?php

namespace App\Http\Resources;

use App\Models\Fund;
use App\Models\Implementation;
use App\Models\NotificationTemplate;
use App\Models\SystemNotification;
use App\Models\SystemNotificationConfig;
use App\Notifications\BaseNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

/**
 * @property SystemNotification $resource
 */
class SystemNotificationResource extends BaseJsonResource
{
    public const array LOAD = [
        'templates',
        'system_notification_configs',
    ];

    protected bool $withLastSentData = false;

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        /** @var Implementation $implementation */
        $notification = $this->resource;
        $templates = $notification->templates;
        $implementation = $request->implementation ?? null;
        $formal = !$implementation->informal_communication;
        $supportsFundTemplates = $implementation->allow_per_fund_notification_templates;

        $configs = $notification->system_notification_configs->where('implementation_id', $implementation->id);
        $config = $configs->whereNull('fund_id')->first();

        return [
            ...$notification->only([
                'id', 'key', 'optional', 'editable', 'group', 'order',
            ]),
            'enable_all' => $config?->enable_all ?? true,
            'enable_mail' => $config?->enable_mail ?? true,
            'enable_push' => $config?->enable_push ?? true,
            'enable_database' => $config?->enable_database ?? true,
            'variables' => BaseNotification::getVariables($notification->key),
            'channels' => $notification->baseChannels(),
            'templates' => NotificationTemplateResource::collection(
                $this->getTemplates($notification, $templates, $implementation, $formal),
            ),
            'templates_default' => NotificationTemplateResource::collection(
                $this->getTemplates($notification, $templates, Implementation::general(), $formal),
            ),
            ...($this->withLastSentData
                ? $this->getLastSentData($notification, $implementation->funds->pluck('id')->toArray())
                : []),
            'funds' => $supportsFundTemplates
                ? $this->getFunds($notification, $implementation, $configs, $this->withLastSentData)
                : null,
        ];
    }

    /**
     * @param SystemNotification $systemNotification
     * @param array $fundIds
     * @return array
     */
    public function getLastSentData(SystemNotification $systemNotification, array $fundIds): array
    {
        if ($systemNotification->key === 'notifications_identities.voucher_expire_soon_budget') {
            return $this->makeTimestamps([
                'last_sent_date' => $systemNotification->getLastSentDate($fundIds),
            ], true);
        }

        return [];
    }

    /**
     * @param SystemNotification $notification
     * @param Collection|NotificationTemplate $templates
     * @param Implementation|null $implementation
     * @param bool $formalCommunication
     * @return Collection
     */
    public function getTemplates(
        SystemNotification $notification,
        Collection|NotificationTemplate $templates,
        ?Implementation $implementation,
        bool $formalCommunication
    ): Collection {
        if (!$implementation) {
            return new Collection();
        }

        $templates = $templates->where('implementation_id', $implementation->id);
        $templates = $templates->where('formal', $formalCommunication);

        if ($implementation->isGeneral() ||
            !$implementation->allow_per_fund_notification_templates) {
            $templates = $templates->whereNull('fund_id');
        }

        return new Collection($templates->values());
    }

    /**
     * @param SystemNotification $notification
     * @param Implementation $implementation
     * @param Collection|SystemNotificationConfig $configs
     * @param bool $withLastSentData
     * @return array
     */
    protected function getFunds(
        SystemNotification $notification,
        Implementation $implementation,
        Collection|SystemNotificationConfig $configs,
        bool $withLastSentData,
    ): array {
        return $implementation->funds->map(function (Fund $fund) use ($notification, $configs, $withLastSentData) {
            $fundConfig = $notification->optional
                ? $configs->where('fund_id', $fund->id)->first()
                : null;

            return [
                'id' => $fund->id,
                'name' => $fund->name,
                'enable_all' => $fundConfig?->enable_all ?? true,
                'enable_mail' => $fundConfig?->enable_mail ?? true,
                'enable_push' => $fundConfig?->enable_push ?? true,
                'enable_database' => $fundConfig?->enable_database ?? true,
                ...($withLastSentData ? $this->getLastSentData($notification, [$fund->id]) : []),
            ];
        })->values()->all();
    }
}
