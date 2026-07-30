<?php

namespace App\Services\CmsService\ImplementationBlocks\Configs;

class LinkPanelsCmsBlockConfig extends CmsBlockConfig
{
    public const string KEY = 'link_panels';
    public const string ITEM_TYPE_PANEL = 'panel';
    public const int COLUMNS_ONE = 1;
    public const int COLUMNS_TWO = 2;
    public const int COLUMNS_THREE = 3;

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
            $this->sectionTitleField(),
            $this->sectionDescriptionField(),
            $this->sectionBackgroundColorField(),
            $this->sectionSpacingField(),
            [
                'key' => 'columns',
                'name' => $this->fieldText('columns', 'name'),
                'type' => self::TYPE_NUMBER,
                'control' => self::CONTROL_SELECT,
                'options' => [[
                    'value' => self::COLUMNS_ONE,
                    'name' => $this->fieldOptionText('columns', 'columns_1'),
                ], [
                    'value' => self::COLUMNS_TWO,
                    'name' => $this->fieldOptionText('columns', 'columns_2'),
                ], [
                    'value' => self::COLUMNS_THREE,
                    'name' => $this->fieldOptionText('columns', 'columns_3'),
                ]],
                'required' => true,
                'default' => self::COLUMNS_TWO,
                'translatable' => false,
            ]];
    }

    /**
     * @return array[]
     */
    public function itemTypes(): array
    {
        return [[
            'key' => self::ITEM_TYPE_PANEL,
            'name' => $this->itemText(self::ITEM_TYPE_PANEL, 'name'),
            'fields' => $this->itemFields(self::ITEM_TYPE_PANEL),
        ]];
    }

    /**
     * @param string $itemTypeKey
     * @return array[]
     */
    public function itemFields(string $itemTypeKey): array
    {
        if ($itemTypeKey !== self::ITEM_TYPE_PANEL) {
            return [];
        }

        return [[
            'key' => 'title',
            'name' => $this->itemFieldText(self::ITEM_TYPE_PANEL, 'title', 'name'),
            'type' => self::TYPE_TEXT,
            'placeholder' => $this->itemFieldText(self::ITEM_TYPE_PANEL, 'title', 'placeholder'),
            'required' => true,
            'max' => 100,
            'translatable' => true,
        ], [
            'key' => 'description',
            'name' => $this->itemFieldText(self::ITEM_TYPE_PANEL, 'description', 'name'),
            'type' => self::TYPE_TEXT,
            'control' => self::CONTROL_TEXTAREA,
            'placeholder' => $this->itemFieldText(self::ITEM_TYPE_PANEL, 'description', 'placeholder'),
            'required' => false,
            'max' => 1000,
            'translatable' => true,
        ], [
            'key' => 'links',
            'name' => $this->itemFieldText(self::ITEM_TYPE_PANEL, 'links', 'name'),
            'type' => self::TYPE_MARKDOWN,
            'placeholder' => $this->itemFieldText(self::ITEM_TYPE_PANEL, 'links', 'placeholder'),
            'required' => false,
            'max' => 3000,
            'translatable' => true,
        ], [
            'key' => 'button_text',
            'name' => $this->itemFieldText(self::ITEM_TYPE_PANEL, 'button_text', 'name'),
            'type' => self::TYPE_TEXT,
            'placeholder' => $this->itemFieldText(self::ITEM_TYPE_PANEL, 'button_text', 'placeholder'),
            'required' => false,
            'max' => 100,
            'translatable' => true,
        ], [
            'key' => 'button_link',
            'name' => $this->itemFieldText(self::ITEM_TYPE_PANEL, 'button_link', 'name'),
            'type' => self::TYPE_URL,
            'placeholder' => $this->itemFieldText(self::ITEM_TYPE_PANEL, 'button_link', 'placeholder'),
            'visible_if_filled' => 'button_text',
            'required_with' => 'button_text',
            'max' => 200,
            'translatable' => false,
        ], [
            'key' => 'button_target_blank',
            'name' => $this->itemFieldText(self::ITEM_TYPE_PANEL, 'button_target_blank', 'name'),
            'type' => self::TYPE_BOOLEAN,
            'control' => self::CONTROL_SELECT,
            'options' => [[
                'value' => false,
                'name' => $this->itemFieldOptionText(self::ITEM_TYPE_PANEL, 'button_target_blank', 'same_tab'),
            ], [
                'value' => true,
                'name' => $this->itemFieldOptionText(self::ITEM_TYPE_PANEL, 'button_target_blank', 'new_tab'),
            ]],
            'visible_if_filled' => 'button_text',
            'required_with' => 'button_text',
            'default' => false,
            'translatable' => false,
        ]];
    }
}
