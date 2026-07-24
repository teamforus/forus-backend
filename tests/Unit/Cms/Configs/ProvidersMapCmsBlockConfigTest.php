<?php

namespace Tests\Unit\Cms\Configs;

use App\Services\CmsService\ImplementationBlocks\Configs\CmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Configs\ProvidersMapCmsBlockConfig;
use Illuminate\Validation\ValidationException;
use Tests\Unit\Cms\CmsBlockTestCase;

class ProvidersMapCmsBlockConfigTest extends CmsBlockTestCase
{
    /**
     * @return void
     */
    public function testProvidersMapConfigFieldsMatchExpectedSchema(): void
    {
        $config = new ProvidersMapCmsBlockConfig();

        $this->assertSame([
            'section_title',
            'section_description',
            'section_background_color',
            'section_spacing',
            'button_text',
        ], array_column($config->fields(), 'key'));
        $this->assertSame([], $config->itemTypes());

        $sectionDescription = $config->field('section_description');
        $buttonText = $config->field('button_text');

        $this->assertSame(CmsBlockConfig::TYPE_TEXT, $sectionDescription['type']);
        $this->assertSame(300, $sectionDescription['max']);

        $this->assertSame(CmsBlockConfig::TYPE_TEXT, $buttonText['type']);
        $this->assertTrue($buttonText['required']);
        $this->assertSame(100, $buttonText['max']);
        $this->assertTrue($buttonText['translatable']);
    }

    /**
     * @throws ValidationException
     * @return void
     */
    public function testAcceptsValidProvidersMapBlockWithoutItems(): void
    {
        $page = $this->makeCmsPageAsOwner();
        $blocks = $this->makeValidCmsProvidersMapBlocksPayload();

        $this->assertBlocksValid($page, null, $blocks);
    }
}
