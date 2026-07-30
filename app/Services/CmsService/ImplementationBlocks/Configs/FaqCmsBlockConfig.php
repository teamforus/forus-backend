<?php

namespace App\Services\CmsService\ImplementationBlocks\Configs;

class FaqCmsBlockConfig extends CmsBlockConfig
{
    public const string KEY = 'faq';
    public const string ITEM_TYPE_ITEM = 'item';
    public const string TYPE_QUESTION = 'question';
    public const string TYPE_TITLE = 'title';

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
        ];
    }

    /**
     * @return array[]
     */
    public function itemTypes(): array
    {
        return [[
            'key' => self::ITEM_TYPE_ITEM,
            'name' => $this->itemText(self::ITEM_TYPE_ITEM, 'name'),
            'fields' => $this->itemFields(self::ITEM_TYPE_ITEM),
        ]];
    }

    /**
     * @param string $itemTypeKey
     * @return array[]
     */
    public function itemFields(string $itemTypeKey): array
    {
        if ($itemTypeKey !== self::ITEM_TYPE_ITEM) {
            return [];
        }

        return [[
            'key' => 'type',
            'name' => $this->itemFieldText(self::ITEM_TYPE_ITEM, 'type', 'name'),
            'type' => self::TYPE_TEXT,
            'hint' => $this->itemFieldText(self::ITEM_TYPE_ITEM, 'type', 'hint'),
            'control' => self::CONTROL_SELECT,
            'options' => [[
                'value' => self::TYPE_QUESTION,
                'name' => $this->itemFieldOptionText(self::ITEM_TYPE_ITEM, 'type', self::TYPE_QUESTION),
            ], [
                'value' => self::TYPE_TITLE,
                'name' => $this->itemFieldOptionText(self::ITEM_TYPE_ITEM, 'type', self::TYPE_TITLE),
            ]],
            'required' => true,
            'default' => self::TYPE_QUESTION,
            'translatable' => false,
        ], [
            'key' => 'title',
            'name' => $this->itemFieldText(self::ITEM_TYPE_ITEM, 'title', 'name'),
            'type' => self::TYPE_TEXT,
            'placeholder' => $this->itemFieldText(self::ITEM_TYPE_ITEM, 'title', 'placeholder'),
            'required' => true,
            'max' => 200,
            'translatable' => true,
        ], [
            'key' => 'subtitle',
            'name' => $this->itemFieldText(self::ITEM_TYPE_ITEM, 'subtitle', 'name'),
            'type' => self::TYPE_TEXT,
            'control' => self::CONTROL_TEXTAREA,
            'placeholder' => $this->itemFieldText(self::ITEM_TYPE_ITEM, 'subtitle', 'placeholder'),
            'visible_if' => ['type', self::TYPE_TITLE],
            'required' => false,
            'max' => 500,
            'translatable' => true,
        ], [
            'key' => 'description',
            'name' => $this->itemFieldText(self::ITEM_TYPE_ITEM, 'description', 'name'),
            'type' => self::TYPE_MARKDOWN,
            'placeholder' => $this->itemFieldText(self::ITEM_TYPE_ITEM, 'description', 'placeholder'),
            'visible_if' => ['type', self::TYPE_QUESTION],
            'required_if' => ['type', self::TYPE_QUESTION],
            'max' => 5000,
            'translatable' => true,
        ]];
    }
}
