<?php

namespace App\Services\CmsService\ImplementationBlocks\Validation;

use App\Models\ImplementationPage;
use App\Rules\MarkdownTextLengthRule;
use App\Rules\MediaUidRule;
use App\Services\CmsService\ImplementationBlocks\Configs\CmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\ImplementationCmsBlockService;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlock;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlockItem;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlockItemValue;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlockValue;
use App\Services\CmsService\ImplementationBlocks\Rules\ImplementationCmsBlockIdRule;
use App\Services\CmsService\ImplementationBlocks\Rules\ImplementationCmsBlockItemIdRule;
use App\Services\CmsService\ImplementationBlocks\Rules\ImplementationCmsBlockTypeRule;
use Illuminate\Validation\Rule;

class ImplementationCmsBlockRuleSet
{
    /**
     * @var string[]
     */
    protected array $mediaValuePaths = [];

    /**
     * @param ImplementationPage|null $page
     * @param string|null $pageType
     */
    public function __construct(
        protected ?ImplementationPage $page,
        protected ?string $pageType,
    ) {
    }

    /**
     * @return array
     */
    public static function baseRules(): array
    {
        return [
            'cms_blocks' => ['nullable', 'array', 'list'],
            'cms_blocks.*' => ['required', 'array'],
            'cms_blocks.*.id' => ['nullable', 'integer', 'distinct'],
            'cms_blocks.*.block_type_key' => ['required', 'string'],
            'cms_blocks.*.state' => [
                'sometimes',
                'required',
                'string',
                Rule::in(ImplementationCmsBlock::STATES),
            ],
            'cms_blocks.*.values' => ['nullable', 'array'],
            'cms_blocks.*.items' => ['nullable', 'array', 'list'],
            'cms_blocks.*.items.*' => ['required', 'array'],
            'cms_blocks.*.items.*.id' => ['nullable', 'integer', 'distinct'],
            'cms_blocks.*.items.*.item_type_key' => ['required', 'string'],
            'cms_blocks.*.items.*.values' => ['nullable', 'array'],
        ];
    }

    /**
     * @param ImplementationPage|null $page
     * @param string|null $pageType
     * @param mixed $blocks
     * @return array
     */
    public static function rules(
        ?ImplementationPage $page,
        ?string $pageType,
        mixed $blocks,
    ): array {
        return (new self($page, $pageType))->build(is_array($blocks) ? $blocks : null);
    }

    /**
     * @param mixed $blocks
     * @return array
     */
    public static function attributes(mixed $blocks): array
    {
        $attributes = [
            'cms_blocks' => self::attributeText('cms_blocks'),
            'cms_blocks.*' => self::attributeText('cms_block'),
            'cms_blocks.*.id' => self::attributeText('cms_block'),
            'cms_blocks.*.block_type_key' => self::attributeText('block_type_key'),
            'cms_blocks.*.state' => self::attributeText('state'),
            'cms_blocks.*.values' => self::attributeText('block_values'),
            'cms_blocks.*.items' => self::attributeText('block_items'),
            'cms_blocks.*.items.*' => self::attributeText('block_item'),
            'cms_blocks.*.items.*.id' => self::attributeText('block_item'),
            'cms_blocks.*.items.*.item_type_key' => self::attributeText('item_type_key'),
            'cms_blocks.*.items.*.values' => self::attributeText('item_values'),
        ];

        if (!is_array($blocks)) {
            return $attributes;
        }

        foreach ($blocks as $blockIndex => $block) {
            if (!is_array($block)) {
                continue;
            }

            self::addBlockAttributes($attributes, $blockIndex, $block);
        }

        return $attributes;
    }

    /**
     * @param array|null $blocks
     * @return array
     */
    protected function build(?array $blocks): array
    {
        $rules = self::baseRules();

        if ($blocks === null) {
            return $rules;
        }

        foreach ($blocks as $blockIndex => $block) {
            if (!is_array($block)) {
                continue;
            }

            $this->addBlockRules($rules, $blockIndex, $block);
        }

        $this->addMediaUidUniquenessRules($rules);

        return $rules;
    }

    /**
     * @param array $attributes
     * @param int|string $blockIndex
     * @param array $block
     * @return void
     */
    protected static function addBlockAttributes(array &$attributes, int|string $blockIndex, array $block): void
    {
        $path = "cms_blocks.$blockIndex";
        $config = self::resolveBlockConfig($block);

        $attributes[$path] = self::attributeText('cms_block') . ' ' . (((int) $blockIndex) + 1);
        $attributes["$path.id"] = $attributes[$path];
        $attributes["$path.block_type_key"] = self::attributeText('block_type_key');
        $attributes["$path.state"] = self::attributeText('state');
        $attributes["$path.values"] = self::attributeText('block_values');
        $attributes["$path.items"] = self::attributeText('block_items');

        if (!$config) {
            return;
        }

        foreach ($config->fields() as $field) {
            $attributes["$path.values.{$field['key']}"] = self::fieldName($field);
        }

        $items = $block['items'] ?? [];

        if (!is_array($items)) {
            return;
        }

        foreach ($items as $itemIndex => $item) {
            if (!is_array($item)) {
                continue;
            }

            $itemPath = "$path.items.$itemIndex";
            $itemTypeKey = $item['item_type_key'] ?? null;
            $itemFields = is_string($itemTypeKey) ? $config->itemFields($itemTypeKey) : [];

            $attributes[$itemPath] = self::attributeText('block_item') . ' ' . (((int) $itemIndex) + 1);
            $attributes["$itemPath.id"] = $attributes[$itemPath];
            $attributes["$itemPath.item_type_key"] = self::attributeText('item_type_key');
            $attributes["$itemPath.values"] = self::attributeText('item_values');

            foreach ($itemFields as $field) {
                $attributes["$itemPath.values.{$field['key']}"] = self::fieldName($field);
            }
        }
    }

    /**
     * @param array $rules
     * @param int|string $blockIndex
     * @param array $block
     * @return void
     */
    protected function addBlockRules(array &$rules, int|string $blockIndex, array $block): void
    {
        $path = "cms_blocks.$blockIndex";
        $config = $this->blockConfig($block);
        $cmsBlock = $this->cmsBlock($block);

        $this->addProhibitedKeys($rules, $path, $block, [
            'id', 'block_type_key', 'state', 'values', 'items',
        ]);

        $rules["$path.block_type_key"] = [
            'required',
            'string',
            new ImplementationCmsBlockTypeRule($this->resolvedPageType(), $cmsBlock),
        ];

        $rules["$path.id"] = [
            ...$rules['cms_blocks.*.id'],
            new ImplementationCmsBlockIdRule($this->page, $block),
        ];

        if (!$config) {
            return;
        }

        $this->addBlockValueRules($rules, $path, $block, $config, $cmsBlock);
        $this->addItemRules($rules, $path, $block, $config, $cmsBlock);
    }

    /**
     * @param array $rules
     * @param string $path
     * @param array $block
     * @param CmsBlockConfig $config
     * @param ImplementationCmsBlock|null $cmsBlock
     * @return void
     */
    protected function addBlockValueRules(
        array &$rules,
        string $path,
        array $block,
        CmsBlockConfig $config,
        ?ImplementationCmsBlock $cmsBlock,
    ): void {
        $valuesPath = "$path.values";
        $values = $block['values'] ?? [];

        if (!is_array($values)) {
            return;
        }

        $this->addProhibitedKeys($rules, $valuesPath, $values, $this->fieldKeys($config->fields()));

        foreach ($config->fields() as $field) {
            $existingMediaValue = $field['type'] === CmsBlockConfig::TYPE_MEDIA ?
                $this->existingBlockMediaValue($cmsBlock, $field['key']) :
                null;

            $rules["$valuesPath.{$field['key']}"] = $this->fieldRules(
                $field,
                $valuesPath,
                $values,
                $existingMediaValue,
            );
        }
    }

    /**
     * @param array $rules
     * @param string $path
     * @param array $block
     * @param CmsBlockConfig $config
     * @param ImplementationCmsBlock|null $cmsBlock
     * @return void
     */
    protected function addItemRules(
        array &$rules,
        string $path,
        array $block,
        CmsBlockConfig $config,
        ?ImplementationCmsBlock $cmsBlock,
    ): void {
        $items = $block['items'] ?? [];

        if (!is_array($items)) {
            return;
        }

        foreach ($items as $itemIndex => $item) {
            if (!is_array($item)) {
                continue;
            }

            $itemPath = "$path.items.$itemIndex";
            $cmsBlockItem = $this->cmsBlockItem($item, $cmsBlock);
            $itemTypeKey = $item['item_type_key'] ?? null;
            $itemFields = is_string($itemTypeKey) ? $config->itemFields($itemTypeKey) : [];

            $this->addProhibitedKeys($rules, $itemPath, $item, ['id', 'item_type_key', 'values']);

            $rules["$itemPath.id"] = [
                ...$rules['cms_blocks.*.items.*.id'],
                new ImplementationCmsBlockItemIdRule($cmsBlock),
            ];
            $rules["$itemPath.item_type_key"] = [
                'required',
                'string',
                Rule::in($this->fieldKeys($config->itemTypes())),
            ];

            $this->addItemValueRules($rules, $itemPath, $item, $itemFields, $cmsBlockItem);
        }
    }

    /**
     * @param array $rules
     * @param string $itemPath
     * @param array $item
     * @param array[] $itemFields
     * @param ImplementationCmsBlockItem|null $cmsBlockItem
     * @return void
     */
    protected function addItemValueRules(
        array &$rules,
        string $itemPath,
        array $item,
        array $itemFields,
        ?ImplementationCmsBlockItem $cmsBlockItem,
    ): void {
        $valuesPath = "$itemPath.values";
        $values = $item['values'] ?? [];

        if (!is_array($values)) {
            return;
        }

        $this->addProhibitedKeys($rules, $valuesPath, $values, $this->fieldKeys($itemFields));

        foreach ($itemFields as $field) {
            $existingMediaValue = $field['type'] === CmsBlockConfig::TYPE_MEDIA ?
                $this->existingItemMediaValue($cmsBlockItem, $field['key']) :
                null;

            $rules["$valuesPath.{$field['key']}"] = $this->fieldRules(
                $field,
                $valuesPath,
                $values,
                $existingMediaValue,
            );
        }
    }

    /**
     * @param array $field
     * @param string $valuesPath
     * @param array $values
     * @param ImplementationCmsBlockValue|ImplementationCmsBlockItemValue|null $existingMediaValue
     * @return array
     */
    protected function fieldRules(
        array $field,
        string $valuesPath,
        array $values,
        ImplementationCmsBlockValue|ImplementationCmsBlockItemValue|null $existingMediaValue = null,
    ): array {
        $isVisible = $this->fieldIsVisible($field, $values);
        $hasVisibilityCondition = isset($field['visible_if']) || isset($field['visible_if_filled']);

        if ($field['type'] === CmsBlockConfig::TYPE_MEDIA && $isVisible) {
            $this->mediaValuePaths[] = "$valuesPath.{$field['key']}";
        }

        $rules = match ($field['type']) {
            CmsBlockConfig::TYPE_TEXT => [
                ...$this->presenceRules($field, $valuesPath),
                'string',
                'max:' . ($field['max'] ?? 191),
                ...$this->optionRules($field),
            ],
            CmsBlockConfig::TYPE_URL => [
                ...$this->presenceRules($field, $valuesPath),
                'string',
                'max:' . ($field['max'] ?? 191),
                'url:http,https',
                ...$this->optionRules($field),
            ],
            CmsBlockConfig::TYPE_MARKDOWN => [
                ...$this->presenceRules($field, $valuesPath),
                'string',
                'max:' . ($field['max'] ?? 25000),
                new MarkdownTextLengthRule(0, $field['max'] ?? null),
            ],
            CmsBlockConfig::TYPE_NUMBER => [
                ...$this->presenceRules($field, $valuesPath),
                'integer',
                isset($field['min']) ? 'min:' . $field['min'] : null,
                isset($field['max']) ? 'max:' . $field['max'] : null,
                ...$this->optionRules($field),
            ],
            CmsBlockConfig::TYPE_BOOLEAN => [
                ...$this->presenceRules($field, $valuesPath),
                'boolean',
            ],
            CmsBlockConfig::TYPE_COLOR => [
                ...$this->presenceRules($field, $valuesPath),
                'hex_color',
            ],
            CmsBlockConfig::TYPE_MEDIA => [
                ...$this->presenceRules($field, $valuesPath),
                'string',
                'exists:media,uid',
                new MediaUidRule($field['media_type'], $existingMediaValue),
            ],
            default => [
                'prohibited',
            ],
        };

        return array_values(array_filter([
            ...($hasVisibilityCondition ? [Rule::excludeIf(!$isVisible)] : []),
            ...$rules,
        ], fn ($rule) => $rule !== null));
    }

    /**
     * @param array $field
     * @param array $values
     * @return bool
     */
    protected function fieldIsVisible(array $field, array $values): bool
    {
        if (isset($field['visible_if'])) {
            [$fieldKey, $expected] = $field['visible_if'];
            $value = $values[$fieldKey] ?? null;

            if (is_bool($expected)) {
                return
                    $value === $expected ||
                    $value === (int) $expected ||
                    $value === ($expected ? '1' : '0');
            }

            return $value === $expected;
        }

        if (isset($field['visible_if_filled'])) {
            $value = $values[$field['visible_if_filled']] ?? null;

            return $value !== null && $value !== '';
        }

        return true;
    }

    /**
     * @param array<string, mixed> $rules
     * @return void
     */
    protected function addMediaUidUniquenessRules(array &$rules): void
    {
        foreach ($this->mediaValuePaths as $index => $mediaValuePath) {
            $previousPaths = array_slice($this->mediaValuePaths, 0, $index);

            if ($previousPaths !== []) {
                $rules[$mediaValuePath][] = 'different:' . implode(',', $previousPaths);
            }
        }
    }

    /**
     * @param array $field
     * @return array
     */
    protected function optionRules(array $field): array
    {
        $options = array_column($field['options'] ?? [], 'value');

        return count($options) > 0 ? [Rule::in($options)] : [];
    }

    /**
     * @param array $field
     * @param string $valuesPath
     * @return string[]
     */
    protected function presenceRules(array $field, string $valuesPath): array
    {
        if ($field['required'] ?? false) {
            return ['required'];
        }

        if (isset($field['required_if'])) {
            [$requiredKey, $requiredValue] = $field['required_if'];

            return ['nullable', "required_if:$valuesPath.$requiredKey," . $this->ruleValue($requiredValue)];
        }

        if (isset($field['required_with'])) {
            return ['nullable', "required_with:$valuesPath.{$field['required_with']}"];
        }

        return ['nullable'];
    }

    /**
     * @param mixed $value
     * @return string
     */
    protected function ruleValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }

    /**
     * @param array $rules
     * @param string $path
     * @param array $payload
     * @param string[] $allowedKeys
     * @return void
     */
    protected function addProhibitedKeys(array &$rules, string $path, array $payload, array $allowedKeys): void
    {
        $rules[$path][] = Rule::array($allowedKeys);

        foreach (array_diff(array_keys($payload), $allowedKeys) as $key) {
            $rules["$path.$key"] = ['prohibited'];
        }
    }

    /**
     * @param array[] $fields
     * @return string[]
     */
    protected function fieldKeys(array $fields): array
    {
        return array_values(array_filter(array_column($fields, 'key'), 'is_string'));
    }

    /**
     * @param array $field
     * @return string
     */
    protected static function fieldName(array $field): string
    {
        $key = $field['key'] ?? null;

        if (is_string($key)) {
            $attribute = self::attributeText($key);

            if ($attribute !== "cms.validation.attributes.$key") {
                return $attribute;
            }
        }

        return strtolower(str_replace('_', ' ', $field['name'] ?? $key ?? 'veld'));
    }

    /**
     * @param string $key
     * @return string
     */
    protected static function attributeText(string $key): string
    {
        return __("cms.validation.attributes.$key");
    }

    /**
     * @param array $block
     * @return CmsBlockConfig|null
     */
    protected function blockConfig(array $block): ?CmsBlockConfig
    {
        $config = self::resolveBlockConfig($block);
        $pageType = $this->resolvedPageType();

        return $config && $pageType && $config->isAllowedForPageType($pageType) ? $config : null;
    }

    /**
     * @param array $block
     * @return CmsBlockConfig|null
     */
    protected static function resolveBlockConfig(array $block): ?CmsBlockConfig
    {
        $blockTypeKey = $block['block_type_key'] ?? null;

        return is_string($blockTypeKey) ? ImplementationCmsBlockService::getBlockConfig($blockTypeKey) : null;
    }

    /**
     * @param array $block
     * @return ImplementationCmsBlock|null
     */
    protected function cmsBlock(array $block): ?ImplementationCmsBlock
    {
        if (!$this->page || !isset($block['id']) || !is_numeric($block['id'])) {
            return null;
        }

        return $this->page->cms_blocks()->find($block['id']);
    }

    /**
     * @param array $item
     * @param ImplementationCmsBlock|null $cmsBlock
     * @return ImplementationCmsBlockItem|null
     */
    protected function cmsBlockItem(array $item, ?ImplementationCmsBlock $cmsBlock): ?ImplementationCmsBlockItem
    {
        if (!$cmsBlock || !isset($item['id']) || !is_numeric($item['id'])) {
            return null;
        }

        $cmsBlockItem = ImplementationCmsBlockItem::query()->find($item['id']);

        return $cmsBlockItem?->implementation_cms_block_id === $cmsBlock->id ? $cmsBlockItem : null;
    }

    /**
     * @param ImplementationCmsBlock|null $cmsBlock
     * @param string $fieldKey
     * @return ImplementationCmsBlockValue|null
     */
    protected function existingBlockMediaValue(
        ?ImplementationCmsBlock $cmsBlock,
        string $fieldKey,
    ): ?ImplementationCmsBlockValue {
        /** @var ImplementationCmsBlockValue|null */
        return $cmsBlock?->values()->where('field_key', $fieldKey)->first();
    }

    /**
     * @param ImplementationCmsBlockItem|null $cmsBlockItem
     * @param string $fieldKey
     * @return ImplementationCmsBlockItemValue|null
     */
    protected function existingItemMediaValue(
        ?ImplementationCmsBlockItem $cmsBlockItem,
        string $fieldKey,
    ): ?ImplementationCmsBlockItemValue {
        /** @var ImplementationCmsBlockItemValue|null */
        return $cmsBlockItem?->values()->where('field_key', $fieldKey)->first();
    }

    /**
     * @return string|null
     */
    protected function resolvedPageType(): ?string
    {
        return $this->page?->page_type ?: $this->pageType;
    }
}
