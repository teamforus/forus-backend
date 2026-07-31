<?php

namespace App\Services\CmsService\ImplementationBlocks\Configs;

class CalloutCmsBlockConfig extends CmsBlockConfig
{
    public const string KEY = 'callout';
    public const string CONTENT_ALIGNMENT_LEFT = 'left';
    public const string CONTENT_ALIGNMENT_CENTER = 'center';
    public const string CONTENT_ALIGNMENT_RIGHT = 'right';

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
            ]),
            $this->sectionBackgroundColorField(),
            $this->sectionSpacingField(),
            [
                'key' => 'image',
                'name' => $this->fieldText('image', 'name'),
                'type' => self::TYPE_MEDIA,
                'media_type' => 'implementation_block_media',
                'required' => false,
                'translatable' => false,
            ], [
                'key' => 'label',
                'name' => $this->fieldText('label', 'name'),
                'type' => self::TYPE_TEXT,
                'placeholder' => $this->fieldText('label', 'placeholder'),
                'required' => false,
                'max' => 30,
                'translatable' => true,
            ], [
                'key' => 'button_enabled',
                'name' => $this->fieldText('button_enabled', 'name'),
                'type' => self::TYPE_BOOLEAN,
                'required' => false,
                'default' => false,
                'translatable' => false,
            ], [
                'key' => 'button_text',
                'name' => $this->fieldText('button_text', 'name'),
                'type' => self::TYPE_TEXT,
                'placeholder' => $this->fieldText('button_text', 'placeholder'),
                'visible_if' => ['button_enabled', true],
                'required_if' => ['button_enabled', true],
                'max' => 200,
                'translatable' => true,
            ], [
                'key' => 'button_link',
                'name' => $this->fieldText('button_link', 'name'),
                'type' => self::TYPE_URL,
                'placeholder' => $this->fieldText('button_link', 'placeholder'),
                'visible_if' => ['button_enabled', true],
                'required_if' => ['button_enabled', true],
                'max' => 200,
                'translatable' => false,
            ], [
                'key' => 'button_target_blank',
                'name' => $this->fieldText('button_target_blank', 'name'),
                'type' => self::TYPE_BOOLEAN,
                'control' => self::CONTROL_SELECT,
                'options' => [[
                    'value' => false,
                    'name' => $this->fieldOptionText('button_target_blank', 'same_tab'),
                ], [
                    'value' => true,
                    'name' => $this->fieldOptionText('button_target_blank', 'new_tab'),
                ]],
                'visible_if' => ['button_enabled', true],
                'required_if' => ['button_enabled', true],
                'default' => false,
                'translatable' => false,
            ], [
                'key' => 'content_alignment',
                'name' => $this->fieldText('content_alignment', 'name'),
                'type' => self::TYPE_TEXT,
                'control' => self::CONTROL_SELECT,
                'options' => [[
                    'value' => self::CONTENT_ALIGNMENT_LEFT,
                    'name' => $this->fieldOptionText('content_alignment', self::CONTENT_ALIGNMENT_LEFT),
                ], [
                    'value' => self::CONTENT_ALIGNMENT_CENTER,
                    'name' => $this->fieldOptionText('content_alignment', self::CONTENT_ALIGNMENT_CENTER),
                ], [
                    'value' => self::CONTENT_ALIGNMENT_RIGHT,
                    'name' => $this->fieldOptionText('content_alignment', self::CONTENT_ALIGNMENT_RIGHT),
                ]],
                'required' => false,
                'default' => self::CONTENT_ALIGNMENT_LEFT,
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
