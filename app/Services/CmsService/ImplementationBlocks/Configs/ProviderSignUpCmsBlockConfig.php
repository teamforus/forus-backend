<?php

namespace App\Services\CmsService\ImplementationBlocks\Configs;

use App\Models\ImplementationPage;

class ProviderSignUpCmsBlockConfig extends CmsBlockConfig
{
    public const string KEY = 'provider_signup';

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
        return [ImplementationPage::TYPE_PROVIDER];
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
            $this->sectionDescriptionField(self::TYPE_MARKDOWN, [
                'default' => $this->fieldText('section_description', 'default'),
            ]),
            $this->sectionBackgroundColorField(),
            $this->sectionSpacingField(),
            [
                'key' => 'image',
                'name' => $this->fieldText('image', 'name'),
                'hint' => $this->fieldText('image', 'hint'),
                'type' => self::TYPE_MEDIA,
                'media_type' => 'implementation_block_media',
                'required' => false,
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
            ], [
                'key' => 'login_enabled',
                'name' => $this->fieldText('login_enabled', 'name'),
                'type' => self::TYPE_BOOLEAN,
                'required' => false,
                'default' => true,
                'translatable' => false,
            ], [
                'key' => 'login_text',
                'name' => $this->fieldText('login_text', 'name'),
                'type' => self::TYPE_TEXT,
                'placeholder' => $this->fieldText('login_text', 'placeholder'),
                'visible_if' => ['login_enabled', true],
                'required' => false,
                'default' => $this->fieldText('login_text', 'default'),
                'max' => 200,
                'translatable' => true,
            ], [
                'key' => 'login_link_text',
                'name' => $this->fieldText('login_link_text', 'name'),
                'type' => self::TYPE_TEXT,
                'placeholder' => $this->fieldText('login_link_text', 'placeholder'),
                'visible_if' => ['login_enabled', true],
                'required' => false,
                'default' => $this->fieldText('login_link_text', 'default'),
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
