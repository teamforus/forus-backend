<?php

namespace App\Http\Resources;

use App\Models\ImplementationBlock;
use App\Models\ImplementationPage;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ImplementationPageResource
 * @property Model|ImplementationPage $resource
 * @package App\Http\Resources
 */
class ImplementationPageResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var ImplementationPage $implementationPage */
        $implementationPage = $this->resource;

        $blocks = $implementationPage?->blocks->map(function (ImplementationBlock $block) {
            $block['media'] = new MediaResource($block->photo);
            $block['description_html'] = $block->description_html;
            return $block;
        });
        $block_list = ImplementationPage::getBlockListByPageKey($implementationPage->page_type);

        return array_merge($implementationPage->only([
            'id', 'page_type', 'content', 'content_alignment',
            'content_html', 'external', 'external_url', 'blocks'
        ]), [
            'blocks'            => $blocks,
            'implementation'    => $implementationPage->implementation->only('id', 'name', 'url_webshop'),
            'text_blocks'       => $block_list[ImplementationBlock::TYPE_TEXT],
            'detailed_blocks'   => $block_list[ImplementationBlock::TYPE_DETAILED],
        ]);
    }
}