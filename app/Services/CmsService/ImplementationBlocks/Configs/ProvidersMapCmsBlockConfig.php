<?php

namespace App\Services\CmsService\ImplementationBlocks\Configs;

use App\Models\ImplementationPage;

class ProvidersMapCmsBlockConfig extends CmsBlockConfig
{
    public const string KEY = 'providers_map';

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
                'max' => 300,
            ]),
            $this->sectionBackgroundColorField(),
            $this->sectionSpacingField(),
            [
                'key' => 'button_text',
                'name' => $this->fieldText('button_text', 'name'),
                'type' => self::TYPE_TEXT,
                'placeholder' => $this->fieldText('button_text', 'placeholder'),
                'required' => true,
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
