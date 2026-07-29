<?php

namespace Tests\Unit\Cms\Configs;

use App\Services\CmsService\ImplementationBlocks\Configs\TextCmsBlockConfig;
use Illuminate\Validation\ValidationException;
use Tests\Unit\Cms\CmsBlockTestCase;

class TextCmsBlockConfigTest extends CmsBlockTestCase
{
    /**
     * @return void
     */
    public function testTextConfigFieldsMatchExpectedSchema(): void
    {
        $config = new TextCmsBlockConfig();

        $this->assertSame([
            'section_title',
            'section_description',
            'section_background_color',
            'section_spacing',
        ], array_column($config->fields(), 'key'));
        $this->assertSame([], $config->itemTypes());

        $this->assertSame(10000, $config->field('section_description')['max']);
    }

    /**
     * @throws ValidationException
     * @return void
     */
    public function testAcceptsValidTextBlockWithoutItems(): void
    {
        $page = $this->makeCmsPageAsOwner();
        $blocks = $this->makeValidCmsTextBlocksPayload();

        $this->assertBlocksValid($page, null, $blocks);
    }
}
