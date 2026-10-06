<?php

namespace Tests\Unit\Cms\Configs;

use App\Services\CmsService\ImplementationBlocks\Configs\CmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Configs\DecisionTreeCmsBlockConfig;
use Illuminate\Validation\ValidationException;
use Tests\Unit\Cms\CmsBlockTestCase;

class DecisionTreeCmsBlockConfigTest extends CmsBlockTestCase
{
    /**
     * @return void
     */
    public function testDecisionTreeFieldsMatchExpectedSchema(): void
    {
        $config = new DecisionTreeCmsBlockConfig();

        $this->assertSame([
            'section_title',
            'section_description',
            'section_title_color',
            'section_spacing',
            'section_background_color',
        ], array_column($config->fields(), 'key'));
        $this->assertSame([], $config->itemTypes());

        $sectionDescription = $config->field('section_description');
        $titleColor = $config->field('section_title_color');
        $sectionBackgroundColor = $config->field('section_background_color');

        $this->assertSame(CmsBlockConfig::TYPE_TEXT, $sectionDescription['type']);
        $this->assertSame(CmsBlockConfig::CONTROL_TEXTAREA, $sectionDescription['control']);
        $this->assertSame(300, $sectionDescription['max']);

        $this->assertSame(CmsBlockConfig::TYPE_COLOR, $titleColor['type']);
        $this->assertFalse($titleColor['required']);
        $this->assertFalse($titleColor['translatable']);

        $this->assertSame(CmsBlockConfig::TYPE_COLOR, $sectionBackgroundColor['type']);
        $this->assertFalse($sectionBackgroundColor['required']);
        $this->assertFalse($sectionBackgroundColor['translatable']);
    }

    /**
     * @throws ValidationException
     * @return void
     */
    public function testAcceptsValidProductCategoriesBlockWithoutItems(): void
    {
        $page = $this->makeCmsPageAsOwner();
        $blocks = $this->makeValidCmsDecisionTreeBlocksPayload();

        $this->assertBlocksValid($page, null, $blocks);
    }
}
