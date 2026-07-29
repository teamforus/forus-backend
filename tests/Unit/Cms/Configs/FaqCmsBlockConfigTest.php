<?php

namespace Tests\Unit\Cms\Configs;

use App\Services\CmsService\ImplementationBlocks\Configs\CmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Configs\FaqCmsBlockConfig;
use Illuminate\Validation\ValidationException;
use Tests\Unit\Cms\CmsBlockTestCase;

class FaqCmsBlockConfigTest extends CmsBlockTestCase
{
    /**
     * @return void
     */
    public function testFaqConfigFieldsMatchExpectedSchema(): void
    {
        $config = new FaqCmsBlockConfig();

        $this->assertSame([
            'section_title',
            'section_description',
            'section_background_color',
            'section_spacing',
        ], array_column($config->fields(), 'key'));
        $this->assertSame([FaqCmsBlockConfig::ITEM_TYPE_ITEM], array_column($config->itemTypes(), 'key'));
        $this->assertSame([], $config->itemFields('unknown'));
        $this->assertSame([
            'type',
            'title',
            'subtitle',
            'description',
        ], array_column($config->itemFields(FaqCmsBlockConfig::ITEM_TYPE_ITEM), 'key'));

        $itemType = $config->itemType(FaqCmsBlockConfig::ITEM_TYPE_ITEM);
        $itemTypeField = $config->itemField(FaqCmsBlockConfig::ITEM_TYPE_ITEM, 'type');
        $itemTitle = $config->itemField(FaqCmsBlockConfig::ITEM_TYPE_ITEM, 'title');
        $subtitle = $config->itemField(FaqCmsBlockConfig::ITEM_TYPE_ITEM, 'subtitle');
        $itemDescription = $config->itemField(FaqCmsBlockConfig::ITEM_TYPE_ITEM, 'description');

        $this->assertSame('FAQ onderdeel', $itemType['name']);

        $this->assertSame(CmsBlockConfig::TYPE_TEXT, $itemTypeField['type']);
        $this->assertSame(CmsBlockConfig::CONTROL_SELECT, $itemTypeField['control']);
        $this->assertTrue($itemTypeField['required']);
        $this->assertSame(FaqCmsBlockConfig::TYPE_QUESTION, $itemTypeField['default']);
        $this->assertSame([
            FaqCmsBlockConfig::TYPE_QUESTION,
            FaqCmsBlockConfig::TYPE_TITLE,
        ], array_column($itemTypeField['options'], 'value'));
        $this->assertFalse($itemTypeField['translatable']);

        $this->assertSame(CmsBlockConfig::TYPE_TEXT, $itemTitle['type']);
        $this->assertTrue($itemTitle['required']);
        $this->assertSame(200, $itemTitle['max']);
        $this->assertTrue($itemTitle['translatable']);

        $this->assertSame(CmsBlockConfig::TYPE_TEXT, $subtitle['type']);
        $this->assertSame(CmsBlockConfig::CONTROL_TEXTAREA, $subtitle['control']);
        $this->assertSame(['type', FaqCmsBlockConfig::TYPE_TITLE], $subtitle['visible_if']);
        $this->assertFalse($subtitle['required']);
        $this->assertSame(500, $subtitle['max']);
        $this->assertTrue($subtitle['translatable']);

        $this->assertSame(CmsBlockConfig::TYPE_MARKDOWN, $itemDescription['type']);
        $this->assertSame(['type', FaqCmsBlockConfig::TYPE_QUESTION], $itemDescription['visible_if']);
        $this->assertSame(['type', FaqCmsBlockConfig::TYPE_QUESTION], $itemDescription['required_if']);
        $this->assertSame(5000, $itemDescription['max']);
        $this->assertTrue($itemDescription['translatable']);
    }

    /**
     * @throws ValidationException
     * @return void
     */
    public function testAcceptsValidFaqBlockWithItems(): void
    {
        $page = $this->makeCmsPageAsOwner();
        $blocks = $this->makeValidCmsFaqBlocksPayload();

        $this->assertBlocksValid($page, null, $blocks);
    }

    /**
     * @return void
     */
    public function testRejectsInvalidFaqType(): void
    {
        $page = $this->makeCmsPageAsOwner();
        $blocks = $this->makeValidCmsFaqBlocksPayload();
        $blocks[0]['items'][0]['values']['type'] = 'unknown';

        $this->assertValidationErrors(function () use ($page, $blocks) {
            $this->validateBlocks($page, null, $blocks);
        }, [
            'cms_blocks.0.items.0.values.type',
        ]);
    }
}
