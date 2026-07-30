<?php

namespace App\Services\CmsService\ImplementationBlocks;

use App\Services\CmsService\ImplementationBlocks\Configs\CmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlock;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlockItem;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlockItemValue;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlockValue;
use App\Services\MediaService\Models\Media;
use App\Support\MarkdownParser;
use Illuminate\Support\Collection;

class ImplementationCmsBlockService
{
    /**
     * @var array<string, CmsBlockConfig>
     */
    protected static array $blockConfigs = [];

    /**
     * @param MarkdownParser $markdownParser
     */
    public function __construct(
        protected MarkdownParser $markdownParser,
    ) {
    }

    /**
     * @param CmsBlockConfig[] $configs
     * @param bool $append
     * @return CmsBlockConfig[]
     */
    public static function setBlockConfigs(array $configs = [], bool $append = true): array
    {
        if (!$append) {
            self::$blockConfigs = [];
        }

        foreach ($configs as $config) {
            self::addBlockConfig($config);
        }

        return self::getBlockConfigs();
    }

    /**
     * @return array<string, CmsBlockConfig>
     */
    public static function getBlockConfigs(): array
    {
        return self::$blockConfigs;
    }

    /**
     * @param CmsBlockConfig $config
     * @return CmsBlockConfig
     */
    public static function addBlockConfig(CmsBlockConfig $config): CmsBlockConfig
    {
        return self::$blockConfigs[$config->key()] = $config;
    }

    /**
     * @param string $key
     * @return CmsBlockConfig|null
     */
    public static function getBlockConfig(string $key): ?CmsBlockConfig
    {
        return self::$blockConfigs[$key] ?? null;
    }

    /**
     * @param string $key
     * @return bool
     */
    public static function hasBlockConfig(string $key): bool
    {
        return isset(self::$blockConfigs[$key]);
    }

    /**
     * @param string $pageType
     * @return CmsBlockConfig[]
     */
    public static function getBlockConfigsForPageType(string $pageType): array
    {
        return array_values(array_filter(
            self::$blockConfigs,
            fn (CmsBlockConfig $config) => $config->isAllowedForPageType($pageType),
        ));
    }

    /**
     * @param ImplementationCmsBlock $block
     * @return array
     */
    public function resolveBlockValues(ImplementationCmsBlock $block): array
    {
        return $this->resolveValues(
            $block->values,
            self::getBlockConfig($block->block_type_key)?->fields() ?? [],
        );
    }

    /**
     * @param ImplementationCmsBlock $block
     * @return array
     */
    public function resolveBlockValuesHtml(ImplementationCmsBlock $block): array
    {
        return $this->resolveValuesHtml(
            $block->values,
            self::getBlockConfig($block->block_type_key)?->fields() ?? [],
        );
    }

    /**
     * @param ImplementationCmsBlock $block
     * @return array<string, Media|null>
     */
    public function resolveBlockMedia(ImplementationCmsBlock $block): array
    {
        $values = $block->values->keyBy('field_key');

        return collect(self::getBlockConfig($block->block_type_key)?->fields() ?? [])
            ->filter(fn (array $field) => $field['type'] === CmsBlockConfig::TYPE_MEDIA)
            ->mapWithKeys(function (array $field) use ($values) {
                $value = $values->get($field['key']);

                return [$field['key'] => $value instanceof ImplementationCmsBlockValue ?
                    $value->medias->first() :
                    null];
            })
            ->all();
    }

    /**
     * @param ImplementationCmsBlockItem $item
     * @return array
     */
    public function resolveItemValues(ImplementationCmsBlockItem $item): array
    {
        return $this->resolveValues(
            $item->values,
            $item->implementation_cms_block
                ?->getCmsConfig()
                ?->itemFields($item->item_type_key) ?? [],
        );
    }

    /**
     * @param ImplementationCmsBlockItem $item
     * @return array
     */
    public function resolveItemValuesHtml(ImplementationCmsBlockItem $item): array
    {
        return $this->resolveValuesHtml(
            $item->values,
            $item->implementation_cms_block
                ?->getCmsConfig()
                ?->itemFields($item->item_type_key) ?? [],
        );
    }

    /**
     * @param ImplementationCmsBlockItem $item
     * @return array<string, Media|null>
     */
    public function resolveItemMedia(ImplementationCmsBlockItem $item): array
    {
        $values = $item->values->keyBy('field_key');

        return collect($item->implementation_cms_block?->getCmsConfig()?->itemFields($item->item_type_key) ?? [])
            ->filter(fn (array $field) => $field['type'] === CmsBlockConfig::TYPE_MEDIA)
            ->mapWithKeys(function (array $field) use ($values) {
                $value = $values->get($field['key']);

                return [$field['key'] => $value instanceof ImplementationCmsBlockItemValue ?
                    $value->medias->first() :
                    null];
            })
            ->all();
    }

    /**
     * @param Collection $values
     * @param array[] $fields
     * @return array
     */
    protected function resolveValues(Collection $values, array $fields): array
    {
        $values = $values->keyBy('field_key');

        return collect($fields)
            ->mapWithKeys(function (array $field) use ($values) {
                $value = $values->get($field['key']);

                return [$field['key'] => $this->resolveValue($value, $field)];
            })
            ->all();
    }

    /**
     * @param Collection $values
     * @param array[] $fields
     * @return array
     */
    protected function resolveValuesHtml(Collection $values, array $fields): array
    {
        $values = $values->keyBy('field_key');

        return collect($fields)
            ->filter(fn (array $field) => $field['type'] === CmsBlockConfig::TYPE_MARKDOWN)
            ->mapWithKeys(function (array $field) use ($values) {
                $value = $values->get($field['key']);

                return [$field['key'] => $this->markdownParser->toHtml($this->resolveValue($value, $field))];
            })
            ->all();
    }

    /**
     * @param ImplementationCmsBlockValue|ImplementationCmsBlockItemValue|null $value
     * @param array $field
     * @return string|null
     */
    protected function resolveValue(
        ImplementationCmsBlockValue|ImplementationCmsBlockItemValue|null $value,
        array $field,
    ): ?string {
        if (!$value) {
            return null;
        }

        if ($field['translatable'] ?? false) {
            return $value->translateColumns(['value' => $value->value])['value'] ?? null;
        }

        return $value->value;
    }
}
