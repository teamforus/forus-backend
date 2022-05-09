<?php

namespace App\Http\Resources;

use App\Models\NotificationTemplate;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property NotificationTemplate $resource
 */
class NotificationTemplateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {

        $template = $this->resource;
        /*$eventLog = resolve(IEventLogService::class);

        $data = array_merge($eventLog->modelToMeta('sponsor', $request->organization ?? null));*/

        return array_merge($template->only([
            'id', 'key', 'type', 'formal', 'implementation_id', 'title', 'content',
        ]), [
            // 'title' => str_var_replace($template->title, $data),
            // 'content' => str_var_replace($template->content, $data),
        ], $template->type == 'mail' ? [
            'content_html' => $template->convertToHtml(),
            // 'content_html' => str_var_replace($template->convertToHtml(), $data),
        ] : []);
    }
}
