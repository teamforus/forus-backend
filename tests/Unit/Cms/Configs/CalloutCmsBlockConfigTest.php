<?php

namespace Tests\Unit\Cms\Configs;

use App\Services\CmsService\ImplementationBlocks\Configs\CalloutCmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Configs\CmsBlockConfig;
use Exception;
use Illuminate\Validation\ValidationException;
use Tests\Unit\Cms\CmsBlockTestCase;

class CalloutCmsBlockConfigTest extends CmsBlockTestCase
{
    /**
     * @return void
     */
    public function testCalloutConfigFieldsMatchExpectedSchema(): void
    {
        $config = new CalloutCmsBlockConfig();

        $this->assertSame([
            'section_title',
            'section_description',
            'section_background_color',
            'section_spacing',
            'image',
            'label',
            'button_enabled',
            'button_text',
            'button_link',
            'button_target_blank',
            'content_alignment',
        ], array_column($config->fields(), 'key'));
        $this->assertSame([], $config->itemTypes());

        $sectionDescription = $config->field('section_description');
        $image = $config->field('image');
        $label = $config->field('label');
        $buttonText = $config->field('button_text');
        $buttonLink = $config->field('button_link');
        $buttonTargetBlank = $config->field('button_target_blank');
        $contentAlignment = $config->field('content_alignment');

        $this->assertSame(CmsBlockConfig::TYPE_TEXT, $sectionDescription['type']);
        $this->assertSame(CmsBlockConfig::CONTROL_TEXTAREA, $sectionDescription['control']);

        $this->assertSame(CmsBlockConfig::TYPE_MEDIA, $image['type']);
        $this->assertSame('implementation_block_media', $image['media_type']);
        $this->assertFalse($image['required']);
        $this->assertFalse($image['translatable']);

        $this->assertSame(CmsBlockConfig::TYPE_TEXT, $label['type']);
        $this->assertFalse($label['required']);
        $this->assertSame(30, $label['max']);
        $this->assertTrue($label['translatable']);

        $this->assertSame(['button_enabled', true], $buttonText['required_if']);
        $this->assertSame(200, $buttonText['max']);
        $this->assertTrue($buttonText['translatable']);

        $this->assertSame(CmsBlockConfig::TYPE_URL, $buttonLink['type']);
        $this->assertSame(['button_enabled', true], $buttonLink['required_if']);
        $this->assertSame(200, $buttonLink['max']);
        $this->assertFalse($buttonLink['translatable']);

        $this->assertSame(CmsBlockConfig::TYPE_BOOLEAN, $buttonTargetBlank['type']);
        $this->assertSame(CmsBlockConfig::CONTROL_SELECT, $buttonTargetBlank['control']);
        $this->assertSame(['button_enabled', true], $buttonTargetBlank['required_if']);
        $this->assertFalse($buttonTargetBlank['default']);

        $this->assertSame(CmsBlockConfig::TYPE_TEXT, $contentAlignment['type']);
        $this->assertSame(CmsBlockConfig::CONTROL_SELECT, $contentAlignment['control']);
        $this->assertFalse($contentAlignment['required']);
        $this->assertSame(CalloutCmsBlockConfig::CONTENT_ALIGNMENT_LEFT, $contentAlignment['default']);
        $this->assertSame([
            CalloutCmsBlockConfig::CONTENT_ALIGNMENT_LEFT,
            CalloutCmsBlockConfig::CONTENT_ALIGNMENT_CENTER,
            CalloutCmsBlockConfig::CONTENT_ALIGNMENT_RIGHT,
        ], array_column($contentAlignment['options'], 'value'));
    }

    /**
     * @throws ValidationException
     * @throws Exception
     * @return void
     */
    public function testAcceptsValidCalloutBlockWithoutItems(): void
    {
        $page = $this->makeCmsPageAsOwner();
        $identity = $page->implementation->organization->identity;
        $media = $this->makeMedia('implementation_block_media', $identity);
        $blocks = $this->makeValidCmsCalloutBlocksPayload($media->uid);

        $this->assertBlocksValid($page, null, $blocks);
    }
}
