<?php

namespace App\Http\Resources;

use App\Models\NotificationTemplate;
use Illuminate\Http\Resources\Json\JsonResource;
use League\CommonMark\Exception\CommonMarkException;

/**
 * @property NotificationTemplate $resource
 */
class NotificationTemplateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @throws CommonMarkException
     * @return array
     */
    public function toArray($request): array
    {
        $template = $this->resource;

        return array_merge($template->only([
            'id', 'key', 'type', 'formal', 'fund_id', 'implementation_id', 'title', 'content',
        ]), [
        ], $template->type == 'mail' ? [
            'content_html' => $template->convertToHtml(),
        ] : []);
    }
}
