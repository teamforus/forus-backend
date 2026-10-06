<?php

namespace App\Services\CmsService\ImplementationBlocks\Configs;

class DecisionTreeCmsBlockConfig extends CmsBlockConfig
{
    public const string KEY = 'decision_tree';

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
            $this->sectionDescriptionField(self::TYPE_TEXT, [
                'control' => self::CONTROL_TEXTAREA,
                'max' => 300,
            ]),
            [
                'key' => 'section_title_color',
                'name' => $this->fieldText('section_title_color', 'name'),
                'type' => self::TYPE_COLOR,
                'placeholder' => $this->fieldText('section_title_color', 'placeholder'),
                'required' => false,
                'translatable' => false,
            ],
            $this->sectionSpacingField(),
            $this->sectionBackgroundColorField(),
        ];
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
