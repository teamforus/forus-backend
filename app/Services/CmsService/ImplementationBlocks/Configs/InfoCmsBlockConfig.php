<?php

namespace App\Services\CmsService\ImplementationBlocks\Configs;

class InfoCmsBlockConfig extends CmsBlockConfig
{
    public const string KEY = 'info';
    public const string ITEM_TYPE_POST = 'post';

    /**
     * @return string
     */
    public function key(): string
    {
        return self::KEY;
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->blockText('name');
    }

    /**
     * @return string[]
     */
    public function allowedPageTypes(): array
    {
        return $this->allowedPageTypesWithGenericCmsBlocks();
    }

    /**
     * @return array[]
     */
    public function fields(): array
    {
        return [
            $this->sectionTitleField([
                'hint' => $this->fieldText('section_title', 'hint'),
            ]),
            $this->sectionDescriptionField(overrides: [
                'hint' => $this->fieldText('section_description', 'hint'),
                'max' => 10000,
            ]),
            $this->sectionBackgroundColorField(),
            $this->sectionSpacingField(),
            [
                'key' => 'blocks_per_row',
                'name' => $this->fieldText('blocks_per_row', 'name'),
                'type' => self::TYPE_NUMBER,
                'options' => [[
                    'value' => 1,
                    'name' => $this->fieldOptionText('blocks_per_row', 'columns_1'),
                ], [
                    'value' => 2,
                    'name' => $this->fieldOptionText('blocks_per_row', 'columns_2'),
                ], [
                    'value' => 3,
                    'name' => $this->fieldOptionText('blocks_per_row', 'columns_3'),
                ]],
                'required' => true,
                'min' => 1,
                'max' => 3,
                'default' => 1,
                'translatable' => false,
            ]];
    }

    /**
     * @return array[]
     */
    public function itemTypes(): array
    {
        return [[
            'key' => self::ITEM_TYPE_POST,
            'name' => $this->itemText(self::ITEM_TYPE_POST, 'name'),
            'fields' => $this->itemFields(self::ITEM_TYPE_POST),
        ]];
    }

    /**
     * @param string $itemTypeKey
     * @return array[]
     */
    public function itemFields(string $itemTypeKey): array
    {
        if ($itemTypeKey !== self::ITEM_TYPE_POST) {
            return [];
        }

        return [[
            'key' => 'media',
            'name' => $this->itemFieldText(self::ITEM_TYPE_POST, 'media', 'name'),
            'type' => self::TYPE_MEDIA,
            'media_type' => 'implementation_block_media',
            'required' => false,
            'translatable' => false,
        ], [
            'key' => 'label',
            'name' => $this->itemFieldText(self::ITEM_TYPE_POST, 'label', 'name'),
            'type' => self::TYPE_TEXT,
            'hint' => $this->itemFieldText(self::ITEM_TYPE_POST, 'label', 'hint'),
            'placeholder' => $this->itemFieldText(self::ITEM_TYPE_POST, 'label', 'placeholder'),
            'required' => false,
            'max' => 30,
            'translatable' => true,
        ], [
            'key' => 'title',
            'name' => $this->itemFieldText(self::ITEM_TYPE_POST, 'title', 'name'),
            'type' => self::TYPE_TEXT,
            'hint' => $this->itemFieldText(self::ITEM_TYPE_POST, 'title', 'hint'),
            'placeholder' => $this->itemFieldText(self::ITEM_TYPE_POST, 'title', 'placeholder'),
            'required' => true,
            'max' => 100,
            'translatable' => true,
        ], [
            'key' => 'description',
            'name' => $this->itemFieldText(self::ITEM_TYPE_POST, 'description', 'name'),
            'type' => self::TYPE_MARKDOWN,
            'hint' => $this->itemFieldText(self::ITEM_TYPE_POST, 'description', 'hint'),
            'placeholder' => $this->itemFieldText(self::ITEM_TYPE_POST, 'description', 'placeholder'),
            'required' => true,
            'max' => 500,
            'translatable' => true,
        ], [
            'key' => 'button_enabled',
            'name' => $this->itemFieldText(self::ITEM_TYPE_POST, 'button_enabled', 'name'),
            'type' => self::TYPE_BOOLEAN,
            'required' => false,
            'default' => false,
            'translatable' => false,
        ], [
            'key' => 'button_text',
            'name' => $this->itemFieldText(self::ITEM_TYPE_POST, 'button_text', 'name'),
            'type' => self::TYPE_TEXT,
            'placeholder' => $this->itemFieldText(self::ITEM_TYPE_POST, 'button_text', 'placeholder'),
            'visible_if' => ['button_enabled', true],
            'required_if' => ['button_enabled', true],
            'max' => 200,
            'translatable' => true,
        ], [
            'key' => 'button_link',
            'name' => $this->itemFieldText(self::ITEM_TYPE_POST, 'button_link', 'name'),
            'type' => self::TYPE_URL,
            'placeholder' => $this->itemFieldText(self::ITEM_TYPE_POST, 'button_link', 'placeholder'),
            'visible_if' => ['button_enabled', true],
            'required_if' => ['button_enabled', true],
            'max' => 200,
            'translatable' => false,
        ], [
            'key' => 'button_link_label',
            'name' => $this->itemFieldText(self::ITEM_TYPE_POST, 'button_link_label', 'name'),
            'type' => self::TYPE_TEXT,
            'hint' => $this->itemFieldText(self::ITEM_TYPE_POST, 'button_link_label', 'hint'),
            'placeholder' => $this->itemFieldText(self::ITEM_TYPE_POST, 'button_link_label', 'placeholder'),
            'visible_if' => ['button_enabled', true],
            'required_if' => ['button_enabled', true],
            'max' => 500,
            'translatable' => true,
        ], [
            'key' => 'button_target_blank',
            'name' => $this->itemFieldText(self::ITEM_TYPE_POST, 'button_target_blank', 'name'),
            'type' => self::TYPE_BOOLEAN,
            'control' => self::CONTROL_SELECT,
            'options' => [[
                'value' => false,
                'name' => $this->itemFieldOptionText(self::ITEM_TYPE_POST, 'button_target_blank', 'same_tab'),
            ], [
                'value' => true,
                'name' => $this->itemFieldOptionText(self::ITEM_TYPE_POST, 'button_target_blank', 'new_tab'),
            ]],
            'visible_if' => ['button_enabled', true],
            'required_if' => ['button_enabled', true],
            'default' => true,
            'translatable' => false,
        ]];
    }
}
