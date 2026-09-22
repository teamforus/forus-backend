<?php

namespace App\Services\CmsService\ImplementationBlocks\Configs;

use App\Models\ImplementationPage;

abstract class CmsBlockConfig
{
    public const string TYPE_TEXT = 'text';
    public const string TYPE_MARKDOWN = 'markdown';
    public const string TYPE_MEDIA = 'media';
    public const string TYPE_BOOLEAN = 'boolean';
    public const string TYPE_URL = 'url';
    public const string TYPE_NUMBER = 'number';
    public const string TYPE_COLOR = 'color';
    public const string CONTROL_SELECT = 'select';
    public const string CONTROL_TEXTAREA = 'textarea';
    public const string SECTION_SPACING_DEFAULT = 'default';
    public const string SECTION_SPACING_NONE = 'none';
    public const string SECTION_SPACING_NO_TOP = 'no_top';
    public const string SECTION_SPACING_NO_BOTTOM = 'no_bottom';

    /**
     * @return string
     */
    abstract public function key(): string;

    /**
     * @return string
     */
    abstract public function name(): string;

    /**
     * @return string[]
     */
    abstract public function allowedPageTypes(): array;

    /**
     * @return array[]
     */
    abstract public function fields(): array;

    /**
     * @return array[]
     */
    abstract public function itemTypes(): array;

    /**
     * @param string $itemTypeKey
     * @return array[]
     */
    abstract public function itemFields(string $itemTypeKey): array;

    /**
     * @param string $pageType
     * @return bool
     */
    public function isAllowedForPageType(string $pageType): bool
    {
        return in_array($pageType, $this->allowedPageTypes(), true);
    }

    /**
     * @param string $fieldKey
     * @return array|null
     */
    public function field(string $fieldKey): ?array
    {
        return $this->findByKey($this->fields(), $fieldKey);
    }

    /**
     * @param string $itemTypeKey
     * @return array|null
     */
    public function itemType(string $itemTypeKey): ?array
    {
        return $this->findByKey($this->itemTypes(), $itemTypeKey);
    }

    /**
     * @param string $itemTypeKey
     * @param string $fieldKey
     * @return array|null
     */
    public function itemField(string $itemTypeKey, string $fieldKey): ?array
    {
        return $this->findByKey($this->itemFields($itemTypeKey), $fieldKey);
    }

    /**
     * @param array[] $items
     * @param string $key
     * @return array|null
     */
    protected function findByKey(array $items, string $key): ?array
    {
        foreach ($items as $item) {
            if (($item['key'] ?? null) === $key) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @return string[]
     */
    protected function allowedPageTypesWithGenericCmsBlocks(): array
    {
        return array_values(array_map(
            fn (array $pageType) => $pageType['key'],
            array_filter(
                ImplementationPage::PAGE_TYPES,
                fn (array $pageType) => $pageType['generic_cms_blocks'] ?? false,
            ),
        ));
    }

    /**
     * @param array $overrides
     * @return array
     */
    protected function sectionTitleField(array $overrides = []): array
    {
        return array_replace([
            'key' => 'section_title',
            'name' => $this->fieldText('section_title', 'name'),
            'type' => self::TYPE_TEXT,
            'placeholder' => $this->fieldText('section_title', 'placeholder'),
            'required' => false,
            'max' => 100,
            'translatable' => true,
        ], $overrides);
    }

    /**
     * @param string $type
     * @param array $overrides
     * @return array
     */
    protected function sectionDescriptionField(string $type = self::TYPE_MARKDOWN, array $overrides = []): array
    {
        return array_replace([
            'key' => 'section_description',
            'name' => $this->fieldText('section_description', 'name'),
            'type' => $type,
            'placeholder' => $this->fieldText('section_description', 'placeholder'),
            'required' => false,
            'max' => 1000,
            'translatable' => true,
        ], $overrides);
    }

    /**
     * @param array $overrides
     * @return array
     */
    protected function sectionBackgroundColorField(array $overrides = []): array
    {
        return array_replace([
            'key' => 'section_background_color',
            'name' => $this->fieldText('section_background_color', 'name'),
            'type' => self::TYPE_COLOR,
            'placeholder' => $this->fieldText('section_background_color', 'placeholder'),
            'required' => false,
            'translatable' => false,
        ], $overrides);
    }

    /**
     * @return array
     */
    protected function sectionSpacingField(): array
    {
        return [
            'key' => 'section_spacing',
            'name' => $this->fieldText('section_spacing', 'name'),
            'type' => self::TYPE_TEXT,
            'control' => self::CONTROL_SELECT,
            'options' => [[
                'value' => self::SECTION_SPACING_DEFAULT,
                'name' => $this->fieldOptionText('section_spacing', self::SECTION_SPACING_DEFAULT),
            ], [
                'value' => self::SECTION_SPACING_NONE,
                'name' => $this->fieldOptionText('section_spacing', self::SECTION_SPACING_NONE),
            ], [
                'value' => self::SECTION_SPACING_NO_TOP,
                'name' => $this->fieldOptionText('section_spacing', self::SECTION_SPACING_NO_TOP),
            ], [
                'value' => self::SECTION_SPACING_NO_BOTTOM,
                'name' => $this->fieldOptionText('section_spacing', self::SECTION_SPACING_NO_BOTTOM),
            ]],
            'required' => false,
            'default' => self::SECTION_SPACING_DEFAULT,
            'translatable' => false,
        ];
    }

    /**
     * @param string $key
     * @return string
     */
    protected function blockText(string $key): string
    {
        return __("cms.blocks.{$this->key()}.$key");
    }

    /**
     * @param string $fieldKey
     * @param string $key
     * @return string
     */
    protected function fieldText(string $fieldKey, string $key): string
    {
        return $this->blockText("fields.$fieldKey.$key");
    }

    /**
     * @param string $fieldKey
     * @param string $optionKey
     * @param string $key
     * @return string
     */
    protected function fieldOptionText(string $fieldKey, string $optionKey, string $key = 'name'): string
    {
        return $this->blockText("fields.$fieldKey.options.$optionKey.$key");
    }

    /**
     * @param string $itemTypeKey
     * @param string $key
     * @return string
     */
    protected function itemText(string $itemTypeKey, string $key): string
    {
        return $this->blockText("items.$itemTypeKey.$key");
    }

    /**
     * @param string $itemTypeKey
     * @param string $fieldKey
     * @param string $key
     * @return string
     */
    protected function itemFieldText(string $itemTypeKey, string $fieldKey, string $key): string
    {
        return $this->itemText($itemTypeKey, "fields.$fieldKey.$key");
    }

    /**
     * @param string $itemTypeKey
     * @param string $fieldKey
     * @param string $optionKey
     * @param string $key
     * @return string
     */
    protected function itemFieldOptionText(
        string $itemTypeKey,
        string $fieldKey,
        string $optionKey,
        string $key = 'name',
    ): string {
        return $this->itemText($itemTypeKey, "fields.$fieldKey.options.$optionKey.$key");
    }
}
