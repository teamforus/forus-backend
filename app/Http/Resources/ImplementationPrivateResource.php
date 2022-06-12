<?php

namespace App\Http\Resources;

use App\Models\Implementation;
use App\Models\ImplementationBlock;
use App\Models\ImplementationPage;

/**
 * Class ImplementationPrivateResource
 * @package App\Http\Resources
 */
class ImplementationPrivateResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @property Implementation $resource
     * @return array
     */
    public function toArray($request): ?array
    {
        /** @var Implementation $implementation **/
        if (is_null($implementation = $this->resource)) {
            return null;
        }
      
        $data = array_merge($implementation->only([
            'id', 'key', 'name', 'url_webshop', 'title',
            'description', 'description_alignment', 'description_html', 'informal_communication',
            'overlay_enabled', 'overlay_type', 'overlay_opacity', 'header_text_color',
        ]), [
            'communication_type' => $implementation->informal_communication ? 'informal' : 'formal',
            'overlay_opacity' => min(max(intval($implementation->overlay_opacity / 10) * 10, 0), 100),
            'banner' => new MediaResource($implementation->banner),
        ]);

        $data = array_merge($data, [
            'pages' => array_reduce(ImplementationPage::TYPES, function(
                array $pages, string $type
            ) use ($implementation) {
                /** @var ImplementationPage $page */
                $page = $implementation->pages()->where('page_type', $type)->get()->first();

                $page?->blocks->map(function (ImplementationBlock $block) {
                    $block['media'] = new MediaResource($block->photo);
                    $block['description_html'] = $block->description_html;
                    unset($block->photo);
                    return $block;
                });

                return array_merge($pages, [$type => $page ? $this->pageDetails($page) : null]);
            }, []),
            'page_types' => ImplementationPage::TYPES,
            'page_types_internal' => ImplementationPage::TYPES_INTERNAL,
        ]);

        return array_merge(
            $data,
            $this->managerDetails($implementation),
            $this->managerCMSDetails($implementation)
        );
    }

    /**
     * @param Implementation $implementation
     * @return array
     */
    protected function managerDetails(Implementation $implementation): array
    {
        if ($implementation->organization->identityCan(auth()->id(), 'implementation_manager')) {
            return $implementation->only([
                'digid_app_id', 'digid_shared_secret', 'digid_a_select_server', 'digid_enabled',
                'email_from_address', 'email_from_name',
            ]);
        }

        return [];
    }

    /**
     * @param Implementation $implementation
     * @return array
     */
    protected function managerCMSDetails(Implementation $implementation): array
    {
        $generalImplementation = $implementation::general();

        if ($implementation->organization->identityCan(auth()->id(), 'implementation_manager_cms')) {
            return [
                'email_logo' => new MediaCompactResource($implementation->email_logo),
                'email_logo_default' => new MediaCompactResource($generalImplementation->email_logo),
                'email_color' => trim(strtoupper($implementation->email_color)),
                'email_color_default' => trim(strtoupper($generalImplementation->email_color)),
                'email_signature' => trim($implementation->email_signature ?: ''),
                'email_signature_default' => trim($generalImplementation->email_signature ?: ''),
            ];
        }

        return [];
    }

    /**
     * @param ImplementationPage|null $page
     * @return array|null
     */
    protected function pageDetails(?ImplementationPage $page): ?array
    {
        $block_list = ImplementationPage::getBlockListByPageKey($page->page_type);

        return array_merge($page?->only([
            'id', 'page_type', 'content', 'content_alignment', 'content_html', 'external', 'external_url', 'blocks'
        ]), [
            'text_blocks'     => $block_list[ImplementationBlock::TYPE_TEXT],
            'detailed_blocks' => $block_list[ImplementationBlock::TYPE_DETAILED],
        ]);
    }
}
