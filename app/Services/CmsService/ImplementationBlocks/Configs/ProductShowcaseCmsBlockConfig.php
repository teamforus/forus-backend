<?php

namespace App\Services\CmsService\ImplementationBlocks\Configs;

use App\Models\ImplementationPage;

class ProductShowcaseCmsBlockConfig extends CmsBlockConfig
{
    public const string KEY = 'product_showcase';
    public const int PRODUCT_COUNT_THREE = 3;
    public const int PRODUCT_COUNT_SIX = 6;
    public const int PRODUCT_COUNT_NINE = 9;
    public const int PRODUCT_COUNT_TWELVE = 12;

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
        return [ImplementationPage::TYPE_HOME];
    }

    /**
     * @return array[]
     */
    public function fields(): array
    {
        return [
            $this->sectionTitleField([
                'default' => $this->fieldText('section_title', 'default'),
            ]),
            $this->sectionDescriptionField(),
            $this->sectionBackgroundColorField(),
            $this->sectionSpacingField(),
            [
                'key' => 'product_count',
                'name' => $this->fieldText('product_count', 'name'),
                'hint' => $this->fieldText('product_count', 'hint'),
                'type' => self::TYPE_NUMBER,
                'control' => self::CONTROL_SELECT,
                'options' => [[
                    'value' => self::PRODUCT_COUNT_THREE,
                    'name' => $this->fieldOptionText('product_count', (string) self::PRODUCT_COUNT_THREE),
                ], [
                    'value' => self::PRODUCT_COUNT_SIX,
                    'name' => $this->fieldOptionText('product_count', (string) self::PRODUCT_COUNT_SIX),
                ], [
                    'value' => self::PRODUCT_COUNT_NINE,
                    'name' => $this->fieldOptionText('product_count', (string) self::PRODUCT_COUNT_NINE),
                ], [
                    'value' => self::PRODUCT_COUNT_TWELVE,
                    'name' => $this->fieldOptionText('product_count', (string) self::PRODUCT_COUNT_TWELVE),
                ]],
                'required' => true,
                'default' => self::PRODUCT_COUNT_SIX,
                'translatable' => false,
            ], [
                'key' => 'button_text',
                'name' => $this->fieldText('button_text', 'name'),
                'type' => self::TYPE_TEXT,
                'placeholder' => $this->fieldText('button_text', 'placeholder'),
                'required' => true,
                'default' => $this->fieldText('button_text', 'default'),
                'max' => 100,
                'translatable' => true,
            ]];
    }

    /**
     * @return array[]
     */
    public function itemTypes(): array
    {
        return [];
    }

    /**
     * @param string $itemTypeKey
     * @return array[]
     */
    public function itemFields(string $itemTypeKey): array
    {
        return [];
    }
}
