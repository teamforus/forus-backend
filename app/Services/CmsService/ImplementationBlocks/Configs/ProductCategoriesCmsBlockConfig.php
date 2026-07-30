<?php

namespace App\Services\CmsService\ImplementationBlocks\Configs;

use App\Models\ImplementationPage;

class ProductCategoriesCmsBlockConfig extends CmsBlockConfig
{
    public const string KEY = 'product_categories';
    public const string BACKGROUND_TYPE_SHAPE = 'shape';
    public const string BACKGROUND_TYPE_SOLID = 'solid';

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
            $this->sectionTitleField(),
            $this->sectionDescriptionField(self::TYPE_TEXT, [
                'control' => self::CONTROL_TEXTAREA,
                'max' => 300,
            ]),
            $this->sectionSpacingField(),
            [
                'key' => 'section_background_type',
                'name' => $this->fieldText('section_background_type', 'name'),
                'type' => self::TYPE_TEXT,
                'control' => self::CONTROL_SELECT,
                'options' => [[
                    'value' => self::BACKGROUND_TYPE_SHAPE,
                    'name' => $this->fieldOptionText('section_background_type', self::BACKGROUND_TYPE_SHAPE),
                ], [
                    'value' => self::BACKGROUND_TYPE_SOLID,
                    'name' => $this->fieldOptionText('section_background_type', self::BACKGROUND_TYPE_SOLID),
                ]],
                'required' => true,
                'default' => self::BACKGROUND_TYPE_SHAPE,
                'translatable' => false,
            ],
            $this->sectionBackgroundColorField([
                'visible_if' => ['section_background_type', self::BACKGROUND_TYPE_SOLID],
            ]),
            [
                'key' => 'section_background_shape_color',
                'name' => $this->fieldText('section_background_shape_color', 'name'),
                'type' => self::TYPE_COLOR,
                'placeholder' => $this->fieldText('section_background_shape_color', 'placeholder'),
                'visible_if' => ['section_background_type', self::BACKGROUND_TYPE_SHAPE],
                'required' => false,
                'translatable' => false,
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
