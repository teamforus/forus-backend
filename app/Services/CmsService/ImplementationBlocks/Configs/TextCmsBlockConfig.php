<?php

namespace App\Services\CmsService\ImplementationBlocks\Configs;

class TextCmsBlockConfig extends CmsBlockConfig
{
    public const string KEY = 'text';

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
