<?php

namespace Tests\Unit\Cms\Configs;

use App\Services\CmsService\ImplementationBlocks\Configs\CmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Configs\ProductCategoriesCmsBlockConfig;
use Illuminate\Validation\ValidationException;
use Tests\Unit\Cms\CmsBlockTestCase;

class ProductCategoriesCmsBlockConfigTest extends CmsBlockTestCase
{
    /**
     * @return void
     */
    public function testProductCategoriesConfigFieldsMatchExpectedSchema(): void
    {
        $config = new ProductCategoriesCmsBlockConfig();

        $this->assertSame([
            'section_title',
            'section_description',
            'section_spacing',
            'section_background_type',
            'section_background_color',
            'section_background_shape_color',
        ], array_column($config->fields(), 'key'));
        $this->assertSame([], $config->itemTypes());

        $sectionDescription = $config->field('section_description');
        $backgroundType = $config->field('section_background_type');
        $shapeColor = $config->field('section_background_shape_color');
        $sectionBackgroundColor = $config->field('section_background_color');

        $this->assertSame(CmsBlockConfig::TYPE_TEXT, $sectionDescription['type']);
        $this->assertSame(CmsBlockConfig::CONTROL_TEXTAREA, $sectionDescription['control']);
        $this->assertSame(300, $sectionDescription['max']);

        $this->assertSame(CmsBlockConfig::TYPE_TEXT, $backgroundType['type']);
        $this->assertSame(CmsBlockConfig::CONTROL_SELECT, $backgroundType['control']);
        $this->assertTrue($backgroundType['required']);
        $this->assertSame(ProductCategoriesCmsBlockConfig::BACKGROUND_TYPE_SHAPE, $backgroundType['default']);
        $this->assertSame([
            ProductCategoriesCmsBlockConfig::BACKGROUND_TYPE_SHAPE,
            ProductCategoriesCmsBlockConfig::BACKGROUND_TYPE_SOLID,
        ], array_column($backgroundType['options'], 'value'));
        $this->assertFalse($backgroundType['translatable']);

        $this->assertSame(CmsBlockConfig::TYPE_COLOR, $shapeColor['type']);
        $this->assertSame(
            ['section_background_type', ProductCategoriesCmsBlockConfig::BACKGROUND_TYPE_SHAPE],
            $shapeColor['visible_if'],
        );
        $this->assertFalse($shapeColor['required']);
        $this->assertFalse($shapeColor['translatable']);

        $this->assertSame(CmsBlockConfig::TYPE_COLOR, $sectionBackgroundColor['type']);
        $this->assertSame(
            ['section_background_type', ProductCategoriesCmsBlockConfig::BACKGROUND_TYPE_SOLID],
            $sectionBackgroundColor['visible_if'],
        );
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
        $blocks = $this->makeValidCmsProductCategoriesBlocksPayload();

        $this->assertBlocksValid($page, null, $blocks);
    }
}
