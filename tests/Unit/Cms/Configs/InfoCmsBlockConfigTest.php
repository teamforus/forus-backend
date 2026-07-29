<?php

namespace Tests\Unit\Cms\Configs;

use App\Services\CmsService\ImplementationBlocks\Configs\CmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Configs\InfoCmsBlockConfig;
use Exception;
use Illuminate\Validation\ValidationException;
use Tests\Unit\Cms\CmsBlockTestCase;

class InfoCmsBlockConfigTest extends CmsBlockTestCase
{
    /**
     * @return void
     */
    public function testInfoConfigParentFieldsMatchExpectedSchema(): void
    {
        $config = new InfoCmsBlockConfig();

        $this->assertSame([
            'section_title',
            'section_description',
            'section_background_color',
            'section_spacing',
            'blocks_per_row',
        ], array_column($config->fields(), 'key'));

        $blocksPerRow = $config->field('blocks_per_row');

        $this->assertSame(10000, $config->field('section_description')['max']);

        $this->assertSame(CmsBlockConfig::TYPE_NUMBER, $blocksPerRow['type']);
        $this->assertTrue($blocksPerRow['required']);
        $this->assertSame(1, $blocksPerRow['min']);
        $this->assertSame(3, $blocksPerRow['max']);
        $this->assertSame(1, $blocksPerRow['default']);
        $this->assertSame([1, 2, 3], array_column($blocksPerRow['options'], 'value'));
        $this->assertFalse($blocksPerRow['translatable']);
    }

    /**
     * @return void
     */
    public function testInfoConfigPostItemFieldsMatchExpectedSchema(): void
    {
        $config = new InfoCmsBlockConfig();
        $itemType = $config->itemType(InfoCmsBlockConfig::ITEM_TYPE_POST);

        $this->assertSame('Bericht', $itemType['name']);
        $this->assertSame([
            'media',
            'label',
            'title',
            'description',
            'button_enabled',
            'button_text',
            'button_link',
            'button_link_label',
            'button_target_blank',
        ], array_column($config->itemFields(InfoCmsBlockConfig::ITEM_TYPE_POST), 'key'));

        $media = $config->itemField(InfoCmsBlockConfig::ITEM_TYPE_POST, 'media');
        $title = $config->itemField(InfoCmsBlockConfig::ITEM_TYPE_POST, 'title');
        $buttonText = $config->itemField(InfoCmsBlockConfig::ITEM_TYPE_POST, 'button_text');
        $buttonTargetBlank = $config->itemField(InfoCmsBlockConfig::ITEM_TYPE_POST, 'button_target_blank');

        $this->assertSame(CmsBlockConfig::TYPE_MEDIA, $media['type']);
        $this->assertSame('implementation_block_media', $media['media_type']);
        $this->assertFalse($media['translatable']);

        $this->assertSame(CmsBlockConfig::TYPE_TEXT, $title['type']);
        $this->assertTrue($title['required']);
        $this->assertSame(100, $title['max']);
        $this->assertTrue($title['translatable']);

        $this->assertSame(['button_enabled', true], $buttonText['required_if']);
        $this->assertSame(200, $buttonText['max']);
        $this->assertTrue($buttonText['translatable']);

        $this->assertSame(CmsBlockConfig::TYPE_BOOLEAN, $buttonTargetBlank['type']);
        $this->assertSame(CmsBlockConfig::CONTROL_SELECT, $buttonTargetBlank['control']);
        $this->assertSame([
            [
                'value' => false,
                'name' => 'Hetzelfde tabblad',
            ], [
                'value' => true,
                'name' => 'Nieuw tabblad',
            ],
        ], $buttonTargetBlank['options']);
        $this->assertSame(['button_enabled', true], $buttonTargetBlank['required_if']);
        $this->assertSame(['button_enabled', true], $buttonTargetBlank['visible_if']);
        $this->assertTrue($buttonTargetBlank['default']);
        $this->assertFalse($buttonTargetBlank['translatable']);
    }

    /**
     * @throws ValidationException
     * @throws Exception
     * @return void
     */
    public function testAcceptsValidInfoBlockWithParentValuesAndMultiplePostItems(): void
    {
        $page = $this->makeCmsPageAsOwner();
        $identity = $page->implementation->organization->identity;
        $media = $this->makeMedia('implementation_block_media', $identity);
        $blocks = $this->makeValidCmsInfoBlocksPayload($media->uid);

        $this->assertBlocksValid($page, null, $blocks);
    }

    /**
     * @return void
     */
    public function testValidatesRequiredAndConditionalFields(): void
    {
        $page = $this->makeCmsPageAsOwner();
        $blocks = $this->makeValidCmsInfoBlocksPayload();

        unset(
            $blocks[0]['values']['blocks_per_row'],
            $blocks[0]['items'][0]['values']['title'],
            $blocks[0]['items'][0]['values']['button_text'],
            $blocks[0]['items'][0]['values']['button_link'],
            $blocks[0]['items'][0]['values']['button_link_label'],
            $blocks[0]['items'][0]['values']['button_target_blank'],
        );

        $this->assertValidationErrors(function () use ($page, $blocks) {
            $this->validateBlocks($page, null, $blocks);
        }, [
            'cms_blocks.0.values.blocks_per_row',
            'cms_blocks.0.items.0.values.title',
            'cms_blocks.0.items.0.values.button_text',
            'cms_blocks.0.items.0.values.button_link',
            'cms_blocks.0.items.0.values.button_link_label',
            'cms_blocks.0.items.0.values.button_target_blank',
        ]);
    }
}
