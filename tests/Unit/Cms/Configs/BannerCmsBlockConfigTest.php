<?php

namespace Tests\Unit\Cms\Configs;

use App\Services\CmsService\ImplementationBlocks\Configs\BannerCmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Configs\CmsBlockConfig;
use Exception;
use Illuminate\Validation\ValidationException;
use Tests\Unit\Cms\CmsBlockTestCase;

class BannerCmsBlockConfigTest extends CmsBlockTestCase
{
    /**
     * @return void
     */
    public function testBannerConfigFieldsMatchExpectedSchema(): void
    {
        $config = new BannerCmsBlockConfig();

        $this->assertSame([
            'section_title',
            'section_description',
            'section_background_color',
            'section_spacing',
            'image',
            'layout',
            'text_background_color',
            'text_color',
            'url',
            'link_label',
            'target_blank',
            'label_enabled',
            'label',
            'label_background_color',
            'label_text_color',
            'button_enabled',
            'link_area',
            'button_label',
            'button_color',
            'button_text_color',
        ], array_column($config->fields(), 'key'));
        $this->assertSame([], $config->itemTypes());

        $sectionDescription = $config->field('section_description');
        $image = $config->field('image');
        $layout = $config->field('layout');
        $textBackgroundColor = $config->field('text_background_color');
        $textColor = $config->field('text_color');
        $label = $config->field('label');
        $labelBackgroundColor = $config->field('label_background_color');
        $labelTextColor = $config->field('label_text_color');
        $url = $config->field('url');
        $linkLabel = $config->field('link_label');
        $targetBlank = $config->field('target_blank');
        $linkArea = $config->field('link_area');
        $buttonLabel = $config->field('button_label');
        $buttonColor = $config->field('button_color');
        $buttonTextColor = $config->field('button_text_color');

        $this->assertSame(CmsBlockConfig::TYPE_TEXT, $sectionDescription['type']);
        $this->assertSame(CmsBlockConfig::CONTROL_TEXTAREA, $sectionDescription['control']);

        $this->assertSame(CmsBlockConfig::TYPE_MEDIA, $image['type']);
        $this->assertSame('implementation_block_media', $image['media_type']);
        $this->assertTrue($image['required']);
        $this->assertFalse($image['translatable']);

        $this->assertSame(CmsBlockConfig::TYPE_TEXT, $layout['type']);
        $this->assertSame(CmsBlockConfig::CONTROL_SELECT, $layout['control']);
        $this->assertTrue($layout['required']);
        $this->assertSame(BannerCmsBlockConfig::LAYOUT_IMAGE_LEFT, $layout['default']);
        $this->assertSame([
            BannerCmsBlockConfig::LAYOUT_IMAGE_LEFT,
            BannerCmsBlockConfig::LAYOUT_IMAGE_RIGHT,
            BannerCmsBlockConfig::LAYOUT_IMAGE_OVERLAY_LEFT,
            BannerCmsBlockConfig::LAYOUT_IMAGE_OVERLAY_CENTER,
            BannerCmsBlockConfig::LAYOUT_IMAGE_OVERLAY_RIGHT,
        ], array_column($layout['options'], 'value'));

        $this->assertSame(CmsBlockConfig::TYPE_COLOR, $textBackgroundColor['type']);
        $this->assertFalse($textBackgroundColor['required']);
        $this->assertFalse($textBackgroundColor['translatable']);
        $this->assertArrayNotHasKey('default', $textBackgroundColor);

        $this->assertSame(CmsBlockConfig::TYPE_COLOR, $textColor['type']);
        $this->assertFalse($textColor['required']);
        $this->assertArrayNotHasKey('default', $textColor);

        $this->assertSame(['label_enabled', true], $label['required_if']);
        $this->assertSame(['label_enabled', true], $label['visible_if']);
        $this->assertSame(30, $label['max']);
        $this->assertTrue($label['translatable']);

        $this->assertSame(CmsBlockConfig::TYPE_COLOR, $labelBackgroundColor['type']);
        $this->assertSame(['label_enabled', true], $labelBackgroundColor['visible_if']);
        $this->assertFalse($labelBackgroundColor['required']);
        $this->assertFalse($labelBackgroundColor['translatable']);

        $this->assertSame(CmsBlockConfig::TYPE_COLOR, $labelTextColor['type']);
        $this->assertSame(['label_enabled', true], $labelTextColor['visible_if']);
        $this->assertFalse($labelTextColor['required']);
        $this->assertFalse($labelTextColor['translatable']);

        $this->assertSame(CmsBlockConfig::TYPE_URL, $url['type']);
        $this->assertFalse($url['required'] ?? false);
        $this->assertSame(['button_enabled', true], $url['required_if']);
        $this->assertSame(200, $url['max']);
        $this->assertFalse($url['translatable']);

        $this->assertSame(CmsBlockConfig::TYPE_TEXT, $linkLabel['type']);
        $this->assertSame('url', $linkLabel['visible_if_filled']);
        $this->assertSame('url', $linkLabel['required_with']);
        $this->assertSame(200, $linkLabel['max']);
        $this->assertTrue($linkLabel['translatable']);

        $this->assertSame(CmsBlockConfig::TYPE_BOOLEAN, $targetBlank['type']);
        $this->assertSame(CmsBlockConfig::CONTROL_SELECT, $targetBlank['control']);
        $this->assertFalse($targetBlank['required']);
        $this->assertFalse($targetBlank['default']);

        $this->assertSame(CmsBlockConfig::TYPE_TEXT, $linkArea['type']);
        $this->assertSame(CmsBlockConfig::CONTROL_SELECT, $linkArea['control']);
        $this->assertSame(['button_enabled', true], $linkArea['required_if']);
        $this->assertSame(['button_enabled', true], $linkArea['visible_if']);
        $this->assertSame(BannerCmsBlockConfig::LINK_AREA_BANNER, $linkArea['default']);
        $this->assertFalse($linkArea['translatable']);
        $this->assertSame([
            BannerCmsBlockConfig::LINK_AREA_BANNER,
            BannerCmsBlockConfig::LINK_AREA_BUTTON,
        ], array_column($linkArea['options'], 'value'));

        $this->assertSame(['button_enabled', true], $buttonLabel['required_if']);
        $this->assertSame(200, $buttonLabel['max']);
        $this->assertTrue($buttonLabel['translatable']);

        $this->assertSame(CmsBlockConfig::TYPE_COLOR, $buttonColor['type']);
        $this->assertSame(['button_enabled', true], $buttonColor['required_if']);
        $this->assertSame('#4E4D40', $buttonColor['default']);

        $this->assertSame(CmsBlockConfig::TYPE_COLOR, $buttonTextColor['type']);
        $this->assertSame(['button_enabled', true], $buttonTextColor['required_if']);
        $this->assertSame('#ffffff', $buttonTextColor['default']);
    }

    /**
     * @throws ValidationException
     * @throws Exception
     * @return void
     */
    public function testAcceptsValidBannerBlockWithoutItems(): void
    {
        $page = $this->makeCmsPageAsOwner();
        $identity = $page->implementation->organization->identity;
        $media = $this->makeMedia('implementation_block_media', $identity);
        $blocks = $this->makeValidCmsBannerBlocksPayload($media->uid);

        $this->assertBlocksValid($page, null, $blocks);
    }
}
