<?php

namespace App\Services\CmsService\ImplementationBlocks\Configs;

use App\Models\ImplementationPage;

class BannerCmsBlockConfig extends CmsBlockConfig
{
    public const string KEY = 'banner';
    public const string LAYOUT_IMAGE_LEFT = 'image_left';
    public const string LAYOUT_IMAGE_RIGHT = 'image_right';
    public const string LAYOUT_IMAGE_OVERLAY_LEFT = 'image_overlay_left';
    public const string LAYOUT_IMAGE_OVERLAY_CENTER = 'image_overlay_center';
    public const string LAYOUT_IMAGE_OVERLAY_RIGHT = 'image_overlay_right';
    public const string LINK_AREA_BANNER = 'banner';
    public const string LINK_AREA_BUTTON = 'button';

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
        return [
            ImplementationPage::TYPE_HOME,
            ImplementationPage::TYPE_EXPLANATION,
            ImplementationPage::TYPE_PRIVACY,
            ImplementationPage::TYPE_ACCESSIBILITY,
            ImplementationPage::TYPE_TERMS_AND_CONDITIONS,
            ImplementationPage::TYPE_PROVIDER,
        ];
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
                'required' => true,
                'translatable' => false,
            ], [
                'key' => 'layout',
                'name' => $this->fieldText('layout', 'name'),
                'type' => self::TYPE_TEXT,
                'control' => self::CONTROL_SELECT,
                'preview_text' => $this->fieldText('layout', 'preview_text'),
                'options' => [[
                    'value' => self::LAYOUT_IMAGE_LEFT,
                    'name' => $this->fieldOptionText('layout', self::LAYOUT_IMAGE_LEFT),
                    'short_name' => $this->fieldOptionText('layout', self::LAYOUT_IMAGE_LEFT, 'short_name'),
                ], [
                    'value' => self::LAYOUT_IMAGE_RIGHT,
                    'name' => $this->fieldOptionText('layout', self::LAYOUT_IMAGE_RIGHT),
                    'short_name' => $this->fieldOptionText('layout', self::LAYOUT_IMAGE_RIGHT, 'short_name'),
                ], [
                    'value' => self::LAYOUT_IMAGE_OVERLAY_LEFT,
                    'name' => $this->fieldOptionText('layout', self::LAYOUT_IMAGE_OVERLAY_LEFT),
                    'short_name' => $this->fieldOptionText('layout', self::LAYOUT_IMAGE_OVERLAY_LEFT, 'short_name'),
                ], [
                    'value' => self::LAYOUT_IMAGE_OVERLAY_CENTER,
                    'name' => $this->fieldOptionText('layout', self::LAYOUT_IMAGE_OVERLAY_CENTER),
                    'short_name' => $this->fieldOptionText('layout', self::LAYOUT_IMAGE_OVERLAY_CENTER, 'short_name'),
                ], [
                    'value' => self::LAYOUT_IMAGE_OVERLAY_RIGHT,
                    'name' => $this->fieldOptionText('layout', self::LAYOUT_IMAGE_OVERLAY_RIGHT),
                    'short_name' => $this->fieldOptionText('layout', self::LAYOUT_IMAGE_OVERLAY_RIGHT, 'short_name'),
                ]],
                'required' => true,
                'default' => self::LAYOUT_IMAGE_LEFT,
                'translatable' => false,
            ], [
                'key' => 'text_background_color',
                'name' => $this->fieldText('text_background_color', 'name'),
                'hint' => $this->fieldText('text_background_color', 'hint'),
                'type' => self::TYPE_COLOR,
                'placeholder' => $this->fieldText('text_background_color', 'placeholder'),
                'required' => false,
                'translatable' => false,
            ], [
                'key' => 'text_color',
                'name' => $this->fieldText('text_color', 'name'),
                'type' => self::TYPE_COLOR,
                'placeholder' => $this->fieldText('text_color', 'placeholder'),
                'required' => false,
                'translatable' => false,
            ], [
                'key' => 'url',
                'name' => $this->fieldText('url', 'name'),
                'hint' => $this->fieldText('url', 'hint'),
                'type' => self::TYPE_URL,
                'placeholder' => $this->fieldText('url', 'placeholder'),
                'required_if' => ['button_enabled', true],
                'max' => 200,
                'translatable' => false,
            ], [
                'key' => 'link_label',
                'name' => $this->fieldText('link_label', 'name'),
                'hint' => $this->fieldText('link_label', 'hint'),
                'type' => self::TYPE_TEXT,
                'placeholder' => $this->fieldText('link_label', 'placeholder'),
                'visible_if_filled' => 'url',
                'required_with' => 'url',
                'max' => 200,
                'translatable' => true,
            ], [
                'key' => 'target_blank',
                'name' => $this->fieldText('target_blank', 'name'),
                'type' => self::TYPE_BOOLEAN,
                'control' => self::CONTROL_SELECT,
                'options' => [[
                    'value' => false,
                    'name' => $this->fieldOptionText('target_blank', 'same_tab'),
                ], [
                    'value' => true,
                    'name' => $this->fieldOptionText('target_blank', 'new_tab'),
                ]],
                'required' => false,
                'default' => false,
                'translatable' => false,
            ], [
                'key' => 'label_enabled',
                'name' => $this->fieldText('label_enabled', 'name'),
                'type' => self::TYPE_BOOLEAN,
                'required' => false,
                'default' => false,
                'translatable' => false,
            ], [
                'key' => 'label',
                'name' => $this->fieldText('label', 'name'),
                'type' => self::TYPE_TEXT,
                'placeholder' => $this->fieldText('label', 'placeholder'),
                'visible_if' => ['label_enabled', true],
                'required_if' => ['label_enabled', true],
                'max' => 30,
                'translatable' => true,
            ], [
                'key' => 'label_background_color',
                'name' => $this->fieldText('label_background_color', 'name'),
                'type' => self::TYPE_COLOR,
                'placeholder' => $this->fieldText('label_background_color', 'placeholder'),
                'visible_if' => ['label_enabled', true],
                'required' => false,
                'translatable' => false,
            ], [
                'key' => 'label_text_color',
                'name' => $this->fieldText('label_text_color', 'name'),
                'type' => self::TYPE_COLOR,
                'placeholder' => $this->fieldText('label_text_color', 'placeholder'),
                'visible_if' => ['label_enabled', true],
                'required' => false,
                'translatable' => false,
            ], [
                'key' => 'button_enabled',
                'name' => $this->fieldText('button_enabled', 'name'),
                'type' => self::TYPE_BOOLEAN,
                'required' => false,
                'default' => false,
                'translatable' => false,
            ], [
                'key' => 'link_area',
                'name' => $this->fieldText('link_area', 'name'),
                'type' => self::TYPE_TEXT,
                'control' => self::CONTROL_SELECT,
                'options' => [[
                    'value' => self::LINK_AREA_BANNER,
                    'name' => $this->fieldOptionText('link_area', self::LINK_AREA_BANNER),
                ], [
                    'value' => self::LINK_AREA_BUTTON,
                    'name' => $this->fieldOptionText('link_area', self::LINK_AREA_BUTTON),
                ]],
                'visible_if' => ['button_enabled', true],
                'required_if' => ['button_enabled', true],
                'default' => self::LINK_AREA_BANNER,
                'translatable' => false,
            ], [
                'key' => 'button_label',
                'name' => $this->fieldText('button_label', 'name'),
                'type' => self::TYPE_TEXT,
                'placeholder' => $this->fieldText('button_label', 'placeholder'),
                'visible_if' => ['button_enabled', true],
                'required_if' => ['button_enabled', true],
                'max' => 200,
                'translatable' => true,
            ], [
                'key' => 'button_color',
                'name' => $this->fieldText('button_color', 'name'),
                'type' => self::TYPE_COLOR,
                'placeholder' => $this->fieldText('button_color', 'placeholder'),
                'visible_if' => ['button_enabled', true],
                'required_if' => ['button_enabled', true],
                'default' => '#4E4D40',
                'translatable' => false,
            ], [
                'key' => 'button_text_color',
                'name' => $this->fieldText('button_text_color', 'name'),
                'type' => self::TYPE_COLOR,
                'placeholder' => $this->fieldText('button_text_color', 'placeholder'),
                'visible_if' => ['button_enabled', true],
                'required_if' => ['button_enabled', true],
                'default' => '#ffffff',
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
