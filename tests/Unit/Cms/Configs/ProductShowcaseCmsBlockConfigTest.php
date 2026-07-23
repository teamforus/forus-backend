<?php

namespace Tests\Unit\Cms\Configs;

use App\Services\CmsService\ImplementationBlocks\Configs\CmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Configs\ProductShowcaseCmsBlockConfig;
use Illuminate\Validation\ValidationException;
use Tests\Unit\Cms\CmsBlockTestCase;

class ProductShowcaseCmsBlockConfigTest extends CmsBlockTestCase
{
    /**
     * @return void
     */
    public function testProductShowcaseConfigFieldsMatchExpectedSchema(): void
    {
        $config = new ProductShowcaseCmsBlockConfig();

        $this->assertSame([
            'section_title',
            'section_description',
            'section_background_color',
            'section_spacing',
            'product_count',
            'button_text',
        ], array_column($config->fields(), 'key'));
        $this->assertSame([], $config->itemTypes());

        $sectionTitle = $config->field('section_title');
        $productCount = $config->field('product_count');
        $buttonText = $config->field('button_text');

        $this->assertSame('Aanbod', $sectionTitle['default']);

        $this->assertSame(CmsBlockConfig::TYPE_NUMBER, $productCount['type']);
        $this->assertSame(CmsBlockConfig::CONTROL_SELECT, $productCount['control']);
        $this->assertTrue($productCount['required']);
        $this->assertSame(ProductShowcaseCmsBlockConfig::PRODUCT_COUNT_SIX, $productCount['default']);
        $this->assertSame([
            ProductShowcaseCmsBlockConfig::PRODUCT_COUNT_THREE,
            ProductShowcaseCmsBlockConfig::PRODUCT_COUNT_SIX,
            ProductShowcaseCmsBlockConfig::PRODUCT_COUNT_NINE,
            ProductShowcaseCmsBlockConfig::PRODUCT_COUNT_TWELVE,
        ], array_column($productCount['options'], 'value'));
        $this->assertFalse($productCount['translatable']);

        $this->assertSame(CmsBlockConfig::TYPE_TEXT, $buttonText['type']);
        $this->assertTrue($buttonText['required']);
        $this->assertSame('Bekijk meer', $buttonText['default']);
        $this->assertSame(100, $buttonText['max']);
        $this->assertTrue($buttonText['translatable']);
    }

    /**
     * @throws ValidationException
     * @return void
     */
    public function testAcceptsValidProductShowcaseBlockWithoutItems(): void
    {
        $page = $this->makeCmsPageAsOwner();
        $blocks = $this->makeValidCmsProductShowcaseBlocksPayload();

        $this->assertBlocksValid($page, null, $blocks);
    }
}
