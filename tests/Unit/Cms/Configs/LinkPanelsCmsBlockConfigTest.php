<?php

namespace Tests\Unit\Cms\Configs;

use App\Services\CmsService\ImplementationBlocks\Configs\CmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Configs\LinkPanelsCmsBlockConfig;
use Illuminate\Validation\ValidationException;
use Tests\Unit\Cms\CmsBlockTestCase;

class LinkPanelsCmsBlockConfigTest extends CmsBlockTestCase
{
    /**
     * @return void
     */
    public function testLinkPanelsConfigFieldsMatchExpectedSchema(): void
    {
        $config = new LinkPanelsCmsBlockConfig();

        $this->assertSame([
            'section_title',
            'section_description',
            'section_background_color',
            'section_spacing',
            'columns',
        ], array_column($config->fields(), 'key'));
        $this->assertSame([LinkPanelsCmsBlockConfig::ITEM_TYPE_PANEL], array_column($config->itemTypes(), 'key'));
        $this->assertSame([], $config->itemFields('unknown'));
        $this->assertSame([
            'title',
            'description',
            'links',
            'button_text',
            'button_link',
            'button_target_blank',
        ], array_column($config->itemFields(LinkPanelsCmsBlockConfig::ITEM_TYPE_PANEL), 'key'));

        $columns = $config->field('columns');
        $itemType = $config->itemType(LinkPanelsCmsBlockConfig::ITEM_TYPE_PANEL);
        $panelTitle = $config->itemField(LinkPanelsCmsBlockConfig::ITEM_TYPE_PANEL, 'title');
        $panelDescription = $config->itemField(LinkPanelsCmsBlockConfig::ITEM_TYPE_PANEL, 'description');
        $links = $config->itemField(LinkPanelsCmsBlockConfig::ITEM_TYPE_PANEL, 'links');
        $buttonText = $config->itemField(LinkPanelsCmsBlockConfig::ITEM_TYPE_PANEL, 'button_text');
        $buttonLink = $config->itemField(LinkPanelsCmsBlockConfig::ITEM_TYPE_PANEL, 'button_link');
        $buttonTargetBlank = $config->itemField(LinkPanelsCmsBlockConfig::ITEM_TYPE_PANEL, 'button_target_blank');

        $this->assertSame(CmsBlockConfig::TYPE_NUMBER, $columns['type']);
        $this->assertSame(CmsBlockConfig::CONTROL_SELECT, $columns['control']);
        $this->assertTrue($columns['required']);
        $this->assertSame(LinkPanelsCmsBlockConfig::COLUMNS_TWO, $columns['default']);
        $this->assertSame([
            LinkPanelsCmsBlockConfig::COLUMNS_ONE,
            LinkPanelsCmsBlockConfig::COLUMNS_TWO,
            LinkPanelsCmsBlockConfig::COLUMNS_THREE,
        ], array_column($columns['options'], 'value'));
        $this->assertFalse($columns['translatable']);

        $this->assertSame('Paneel', $itemType['name']);

        $this->assertSame(CmsBlockConfig::TYPE_TEXT, $panelTitle['type']);
        $this->assertTrue($panelTitle['required']);
        $this->assertSame(100, $panelTitle['max']);
        $this->assertTrue($panelTitle['translatable']);

        $this->assertSame(CmsBlockConfig::TYPE_TEXT, $panelDescription['type']);
        $this->assertSame(CmsBlockConfig::CONTROL_TEXTAREA, $panelDescription['control']);
        $this->assertFalse($panelDescription['required']);
        $this->assertSame(1000, $panelDescription['max']);
        $this->assertTrue($panelDescription['translatable']);

        $this->assertSame(CmsBlockConfig::TYPE_MARKDOWN, $links['type']);
        $this->assertFalse($links['required']);
        $this->assertSame(3000, $links['max']);
        $this->assertTrue($links['translatable']);

        $this->assertSame(CmsBlockConfig::TYPE_TEXT, $buttonText['type']);
        $this->assertFalse($buttonText['required']);
        $this->assertSame(100, $buttonText['max']);
        $this->assertTrue($buttonText['translatable']);

        $this->assertSame(CmsBlockConfig::TYPE_URL, $buttonLink['type']);
        $this->assertSame('button_text', $buttonLink['visible_if_filled']);
        $this->assertSame('button_text', $buttonLink['required_with']);
        $this->assertSame(200, $buttonLink['max']);
        $this->assertFalse($buttonLink['translatable']);

        $this->assertSame(CmsBlockConfig::TYPE_BOOLEAN, $buttonTargetBlank['type']);
        $this->assertSame(CmsBlockConfig::CONTROL_SELECT, $buttonTargetBlank['control']);
        $this->assertSame('button_text', $buttonTargetBlank['visible_if_filled']);
        $this->assertSame('button_text', $buttonTargetBlank['required_with']);
        $this->assertFalse($buttonTargetBlank['default']);
        $this->assertFalse($buttonTargetBlank['translatable']);
    }

    /**
     * @throws ValidationException
     * @return void
     */
    public function testAcceptsValidLinkPanelsBlockWithPanelItems(): void
    {
        $page = $this->makeCmsPageAsOwner();
        $blocks = $this->makeValidCmsLinkPanelsBlocksPayload();

        $this->assertBlocksValid($page, null, $blocks);
    }
}
