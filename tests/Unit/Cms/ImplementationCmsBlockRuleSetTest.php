<?php

namespace Tests\Unit\Cms;

use App\Models\ImplementationPage;
use App\Services\CmsService\ImplementationBlocks\Configs\BannerCmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlock;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlockItemValue;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlockValue;
use App\Services\CmsService\ImplementationBlocks\Validation\ImplementationCmsBlockRuleSet;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ImplementationCmsBlockRuleSetTest extends CmsBlockTestCase
{
    /**
     * @throws ValidationException
     * @return void
     */
    public function testAcceptsSupportedCmsBlockStates(): void
    {
        $page = $this->makeCmsPageAsOwner();

        foreach (ImplementationCmsBlock::STATES as $state) {
            $blocks = $this->makeValidCmsTextBlocksPayload();
            $blocks[0]['state'] = $state;

            $this->assertBlocksValid($page, null, $blocks);
        }
    }

    /**
     * @return void
     */
    public function testRejectsUnsupportedCmsBlockState(): void
    {
        $page = $this->makeCmsPageAsOwner();
        $blocks = $this->makeValidCmsTextBlocksPayload();
        $blocks[0]['state'] = 'invalid';

        $this->assertValidationErrors(
            fn () => $this->validateBlocks($page, null, $blocks),
            ['cms_blocks.0.state'],
        );
    }

    /**
     * @return void
     */
    public function testRejectsScalarCmsBlocksWithoutThrowingTypeError(): void
    {
        $page = $this->makeCmsPageAsOwner();
        $blocks = 'invalid';

        $validator = Validator::make([
            'cms_blocks' => $blocks,
        ], ImplementationCmsBlockRuleSet::rules(
            $page,
            null,
            $blocks,
        ), [], ImplementationCmsBlockRuleSet::attributes($blocks));

        $this->assertArrayHasKey('cms_blocks', $validator->errors()->toArray());
    }

    /**
     * @return void
     */
    public function testRejectsAssociativeCmsBlockCollection(): void
    {
        $page = $this->makeCmsPageAsOwner();
        $blocks = ['text' => $this->makeValidCmsTextBlocksPayload()[0]];

        $this->assertValidationErrors(function () use ($page, $blocks) {
            $this->validateBlocks($page, null, $blocks);
        }, ['cms_blocks']);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testRejectsAssociativeCmsBlockItemCollection(): void
    {
        $page = $this->makeCmsPageAsOwner();
        $identity = $page->implementation->organization->identity;
        $media = $this->makeMedia('implementation_block_media', $identity);
        $blocks = $this->makeValidCmsInfoBlocksPayload($media->uid);
        $blocks[0]['items'] = ['post' => $blocks[0]['items'][0]];

        $this->assertValidationErrors(function () use ($page, $blocks) {
            $this->validateBlocks($page, null, $blocks);
        }, ['cms_blocks.0.items']);
    }

    /**
     * @return void
     */
    public function testRejectsBlockTypeNotAllowedForPageType(): void
    {
        $page = $this->makeCmsPageAsOwner(ImplementationPage::TYPE_PRODUCTS);

        $this->assertValidationErrors(function () use ($page) {
            $this->validateBlocks($page, null, $this->makeValidCmsInfoBlocksPayload());
        }, ['cms_blocks.0.block_type_key']);
    }

    /**
     * @throws ValidationException
     * @return void
     */
    public function testAcceptsExistingCmsBlockWithMissingConfig(): void
    {
        $page = $this->makeCmsPageAsOwner();
        $block = $this->createCmsBlock($page, 'removed_block_type');
        $blocks = [[
            'id' => $block->id,
            'block_type_key' => $block->block_type_key,
            'state' => ImplementationCmsBlock::STATE_PUBLIC,
            'values' => [],
            'items' => [],
        ]];

        $this->assertBlocksValid($page, null, $blocks);
    }

    /**
     * @throws ValidationException
     * @return void
     */
    public function testAcceptsExistingCmsBlockNoLongerAllowedForPageType(): void
    {
        $page = $this->makeCmsPageAsOwner(ImplementationPage::TYPE_PRODUCTS);
        $block = $this->createCmsBlock($page);
        $blocks = $this->makeValidCmsInfoBlocksPayload();
        $blocks[0]['id'] = $block->id;

        $this->assertBlocksValid($page, null, $blocks);
    }

    /**
     * @return void
     */
    public function testRejectsNewCmsBlockWithMissingConfig(): void
    {
        $page = $this->makeCmsPageAsOwner();
        $blocks = $this->makeValidCmsInfoBlocksPayload();
        $blocks[0]['block_type_key'] = 'removed_block_type';

        $this->assertValidationErrors(function () use ($page, $blocks) {
            $this->validateBlocks($page, null, $blocks);
        }, ['cms_blocks.0.block_type_key']);
    }

    /**
     * @return void
     */
    public function testRejectsUnknownParentAndItemFieldKeys(): void
    {
        $page = $this->makeCmsPageAsOwner();
        $blocks = $this->makeValidCmsInfoBlocksPayload();
        $blocks[0]['values']['unknown_parent_field'] = 'value';
        $blocks[0]['items'][0]['values']['unknown_item_field'] = 'value';

        $this->assertValidationErrors(function () use ($page, $blocks) {
            $this->validateBlocks($page, null, $blocks);
        }, [
            'cms_blocks.0.values.unknown_parent_field',
            'cms_blocks.0.items.0.values.unknown_item_field',
        ]);
    }

    /**
     * @return void
     */
    public function testRejectsInvalidItemType(): void
    {
        $page = $this->makeCmsPageAsOwner();
        $blocks = $this->makeValidCmsInfoBlocksPayload();
        $blocks[0]['items'][0]['item_type_key'] = 'unknown';

        $this->assertValidationErrors(function () use ($page, $blocks) {
            $this->validateBlocks($page, null, $blocks);
        }, ['cms_blocks.0.items.0.item_type_key']);
    }

    /**
     * @return void
     */
    public function testRejectsIdsOutsideTheCurrentPage(): void
    {
        $page = $this->makeCmsPageAsOwner();
        $otherPage = $this->makeCmsPageAsOwner();

        $otherBlock = $this->createCmsBlock($otherPage);
        $currentBlock = $this->createCmsBlock($page);
        $otherCurrentBlock = $this->createCmsBlock($page);
        $otherCurrentItem = $this->createCmsItem($otherCurrentBlock);
        $blocks = $this->makeValidCmsInfoBlocksPayload();

        $blocks[0]['id'] = $otherBlock->id;
        $blocks[0]['items'][0]['id'] = $otherCurrentItem->id;

        $this->assertValidationErrors(function () use ($page, $blocks) {
            $this->validateBlocks($page, null, $blocks);
        }, [
            'cms_blocks.0.id',
            'cms_blocks.0.items.0.id',
        ]);

        $blocks[0]['id'] = $currentBlock->id;

        $this->assertValidationErrors(function () use ($page, $blocks) {
            $this->validateBlocks($page, null, $blocks);
        }, ['cms_blocks.0.items.0.id']);
    }

    /**
     * @return void
     */
    public function testRejectsExistingBlockIdFromDifferentPageInSameImplementation(): void
    {
        $page = $this->makeCmsPageAsOwner();
        $otherPage = $page->implementation->pages()->create([
            'page_type' => ImplementationPage::TYPE_PRIVACY,
            'state' => ImplementationPage::STATE_PUBLIC,
            'external' => false,
            'description_position' => ImplementationPage::DESCRIPTION_POSITION_REPLACE,
            'description_alignment' => 'left',
            'blocks_per_row' => 3,
        ]);
        $otherBlock = $this->createCmsBlock($otherPage);
        $blocks = $this->makeValidCmsInfoBlocksPayload();
        $blocks[0]['id'] = $otherBlock->id;

        $this->assertValidationErrors(function () use ($page, $blocks) {
            $this->validateBlocks($page, null, $blocks);
        }, ['cms_blocks.0.id']);
    }

    /**
     * @return void
     */
    public function testRejectsExistingBlockIdWhenCreatingPage(): void
    {
        $page = $this->makeCmsPageAsOwner();

        $block = $this->createCmsBlock($page);
        $blocks = $this->makeValidCmsInfoBlocksPayload();
        $blocks[0]['id'] = $block->id;

        $this->assertValidationErrors(function () use ($blocks) {
            $this->validateBlocks(null, ImplementationPage::TYPE_HOME, $blocks);
        }, ['cms_blocks.0.id']);
    }

    /**
     * @throws ValidationException
     * @return void
     */
    public function testAcceptsExistingBlockOnCurrentPage(): void
    {
        $page = $this->makeCmsPageAsOwner();

        $block = $this->createCmsBlock($page);
        $blocks = $this->makeValidCmsInfoBlocksPayload();
        $blocks[0]['id'] = $block->id;

        $this->assertBlocksValid($page, null, $blocks);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testRejectsDuplicateMediaUidAcrossCmsBlockValues(): void
    {
        $page = $this->makeCmsPageAsOwner();
        $identity = $page->implementation->organization->identity;
        $media = $this->makeMedia('implementation_block_media', $identity);
        $banner = $this->makeValidCmsBannerBlocksPayload($media->uid)[0];
        $blocks = [$banner, $banner];

        $this->assertValidationErrors(function () use ($page, $blocks) {
            $this->validateBlocks($page, null, $blocks);
        }, ['cms_blocks.1.values.image']);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testRejectsDuplicateMediaUidAcrossBlockAndItemValues(): void
    {
        $page = $this->makeCmsPageAsOwner();
        $identity = $page->implementation->organization->identity;
        $media = $this->makeMedia('implementation_block_media', $identity);

        $blocks = [
            $this->makeValidCmsBannerBlocksPayload($media->uid)[0],
            $this->makeValidCmsInfoBlocksPayload($media->uid)[0],
        ];

        $this->assertValidationErrors(function () use ($page, $blocks) {
            $this->validateBlocks($page, null, $blocks);
        }, ['cms_blocks.1.items.0.values.media']);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testRejectsDuplicateMediaUidAcrossCmsBlockItems(): void
    {
        $page = $this->makeCmsPageAsOwner();
        $identity = $page->implementation->organization->identity;
        $media = $this->makeMedia('implementation_block_media', $identity);
        $blocks = $this->makeValidCmsInfoBlocksPayload($media->uid);
        $blocks[0]['items'][1]['values']['media'] = $media->uid;

        $this->assertValidationErrors(function () use ($page, $blocks) {
            $this->validateBlocks($page, null, $blocks);
        }, ['cms_blocks.0.items.1.values.media']);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testUsesReadableCmsFieldAttributeNames(): void
    {
        app()->setLocale('nl');

        $page = $this->makeCmsPageAsOwner();
        $identity = $page->implementation->organization->identity;
        $media = $this->makeMedia('implementation_block_media', $identity);
        $blocks = [
            $this->makeValidCmsBannerBlocksPayload($media->uid)[0],
            $this->makeValidCmsBannerBlocksPayload($media->uid)[0],
        ];

        unset($blocks[1]['values']['image']);

        $validator = Validator::make(
            ['cms_blocks' => $blocks],
            ImplementationCmsBlockRuleSet::rules($page, null, $blocks),
            [],
            ImplementationCmsBlockRuleSet::attributes($blocks),
        );

        $this->assertSame(
            'Het afbeelding veld is verplicht.',
            $validator->errors()->first('cms_blocks.1.values.image'),
        );
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testValidatesMediaType(): void
    {
        $page = $this->makeCmsPageAsOwner();
        $identity = $page->implementation->organization->identity;
        $media = $this->makeMedia('cms_media', $identity);
        $blocks = $this->makeValidCmsInfoBlocksPayload($media->uid);

        $this->assertValidationErrors(function () use ($page, $blocks) {
            $this->validateBlocks($page, null, $blocks);
        }, ['cms_blocks.0.items.0.values.media']);
    }

    /**
     * @throws ValidationException
     * @throws Exception
     * @return void
     */
    public function testAcceptsMediaAlreadyOwnedByCurrentCmsItemValue(): void
    {
        $page = $this->makeCmsPageAsOwner();
        $identity = $page->implementation->organization->identity;
        $otherIdentity = $this->makeIdentity();
        $block = $this->createCmsBlock($page);
        $item = $this->createCmsItem($block);
        $itemValue = $item->values()->create([
            'field_key' => 'media',
            'value' => null,
        ]);
        $media = $this->makeMedia('implementation_block_media', $otherIdentity);

        $itemValue->syncMedia([$media->uid], 'implementation_block_media');

        $blocks = $this->makeValidCmsInfoBlocksPayload($media->uid);
        $blocks[0]['id'] = $block->id;
        $blocks[0]['items'][0]['id'] = $item->id;

        $this->assertBlocksValid($page, null, $blocks);
        $this->assertSame($identity->address, auth()->user()->address);
    }

    /**
     * @throws ValidationException
     * @throws Exception
     * @return void
     */
    public function testAcceptsMediaAlreadyOwnedByCurrentCmsBlockValue(): void
    {
        $page = $this->makeCmsPageAsOwner();
        $otherIdentity = $this->makeIdentity();
        $block = $this->createCmsBlock($page, BannerCmsBlockConfig::KEY);
        $blockValue = $block->values()->create([
            'field_key' => 'image',
            'value' => null,
        ]);
        $media = $this->makeMedia('implementation_block_media', $otherIdentity);

        $blockValue->syncMedia([$media->uid], 'implementation_block_media');

        $blocks = $this->makeValidCmsBannerBlocksPayload($media->uid);
        $blocks[0]['id'] = $block->id;

        $this->assertBlocksValid($page, null, $blocks);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testRejectsCmsItemValueMediaWhenCmsBlockValueIdCollides(): void
    {
        $page = $this->makeCmsPageAsOwner();
        $identity = $page->implementation->organization->identity;
        $collisionId = max(
            ImplementationCmsBlockValue::query()->max('id') ?? 0,
            ImplementationCmsBlockItemValue::query()->max('id') ?? 0,
        ) + 1;
        $bannerBlock = $this->createCmsBlock($page, BannerCmsBlockConfig::KEY);
        $infoBlock = $this->createCmsBlock($page);
        $item = $this->createCmsItem($infoBlock);

        $bannerBlock->values()->forceCreate([
            'id' => $collisionId,
            'field_key' => 'image',
            'value' => null,
        ]);

        $itemValue = $item->values()->forceCreate([
            'id' => $collisionId,
            'field_key' => 'media',
            'value' => null,
        ]);

        $media = $this->makeMedia('implementation_block_media', $identity);
        $itemValue->syncMedia([$media->uid], 'implementation_block_media');

        $blocks = $this->makeValidCmsBannerBlocksPayload($media->uid);
        $blocks[0]['id'] = $bannerBlock->id;

        $this->assertValidationErrors(function () use ($page, $blocks) {
            $this->validateBlocks($page, null, $blocks);
        }, ['cms_blocks.0.values.image']);
    }
}
