<?php

namespace App\Services\CmsService\ImplementationBlocks\Resources;

use App\Http\Resources\BaseJsonResource;
use App\Services\CmsService\ImplementationBlocks\Configs\CmsBlockConfig;
use App\Support\MarkdownParser;
use Illuminate\Http\Request;
use League\CommonMark\Exception\CommonMarkException;

/**
 * @property CmsBlockConfig $resource
 */
class ImplementationCmsBlockConfigResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @throws CommonMarkException
     * @return array
     */
    public function toArray(Request $request): array
    {
        $config = $this->resource;
        $markdownParser = resolve(MarkdownParser::class);

        return [
            'key' => $config->key(),
            'name' => $config->name(),
            'allowed_page_types' => $config->allowedPageTypes(),
            'fields' => $this->fieldsWithDefaultHtml($config->fields(), $markdownParser),
            'item_types' => array_map(fn (array $itemType) => [
                ...$itemType,
                'fields' => $this->fieldsWithDefaultHtml($itemType['fields'], $markdownParser),
            ], $config->itemTypes()),
        ];
    }

    /**
     * @param array[] $fields
     * @param MarkdownParser $markdownParser
     * @throws CommonMarkException
     * @return array[]
     */
    protected function fieldsWithDefaultHtml(array $fields, MarkdownParser $markdownParser): array
    {
        return array_map(function (array $field) use ($markdownParser) {
            if ($field['type'] === CmsBlockConfig::TYPE_MARKDOWN && array_key_exists('default', $field)) {
                $field['default_html'] = $markdownParser->toHtml((string) $field['default']);
            }

            return $field;
        }, $fields);
    }
}
