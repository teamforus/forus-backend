<?php

namespace Tests\Unit\Cms;

use App\Models\Identity;
use App\Models\Implementation;
use App\Models\ImplementationBlock;
use App\Models\ImplementationPage;
use App\Services\CmsService\ImplementationBlocks\Configs\InfoCmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Configs\TextCmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\ImplementationCmsBlockSyncService;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlock;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlockItemValue;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlockValue;
use App\Services\MediaService\Models\Media;
use App\Services\TranslationService\Models\TranslationValue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Traits\MakesCmsMedia;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class ImplementationCmsBlockSyncServiceTest extends TestCase
{
    use DatabaseTransactions;
    use MakesCmsMedia;
    use MakesTestFunds;
    use MakesTestOrganizations;

    /**
     * @throws Throwable
     * @return void
     */
    public function testCreatesInfoBlockWithParentValuesAndMultiplePosts(): void
    {
        [$identity, , $page] = $this->makeCmsSetup();
        $media = $this->makeMedia('implementation_block_media', $identity);

        $this->syncService()->sync($page, $this->makeValidCmsInfoBlocksPayload($media->uid));

        $block = $page->cms_blocks()->first();
        $items = $block->items()->with('values')->get();
        $mediaValue = $items[0]->values->firstWhere('field_key', 'media');

        $this->assertSame(1, $page->cms_blocks()->count());
        $this->assertSame(InfoCmsBlockConfig::KEY, $block->block_type_key);
        $this->assertSame(0, $block->order);
        $this->assertSame(ImplementationCmsBlock::STATE_DRAFT, $block->state);
        $this->assertSame('Section title', $this->blockValue($block, 'section_title'));
        $this->assertSame('3', $this->blockValue($block, 'blocks_per_row'));
        $this->assertSame([0, 1], $items->pluck('order')->all());
        $this->assertSame('First post', $this->itemValue($items[0]->id, 'title'));
        $this->assertSame('Second post', $this->itemValue($items[1]->id, 'title'));
        $this->assertSame($media->uid, $mediaValue->value);

        $media->refresh();
        $this->assertSame('implementation_cms_block_item_value', $media->mediable_type);
        $this->assertSame($mediaValue->id, $media->mediable_id);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testCreatesTextBlockWithParentValuesWithoutItems(): void
    {
        [, , $page] = $this->makeCmsSetup();

        $this->syncService()->sync($page, $this->makeValidCmsTextBlocksPayload());

        $block = $page->cms_blocks()->first();

        $this->assertSame(1, $page->cms_blocks()->count());
        $this->assertSame(TextCmsBlockConfig::KEY, $block->block_type_key);
        $this->assertSame(0, $block->order);
        $this->assertSame('Text section title', $this->blockValue($block, 'section_title'));
        $this->assertSame('Text section description', $this->blockValue($block, 'section_description'));
        $this->assertSame(0, $block->items()->count());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testUpdatesAndPreservesCmsBlockState(): void
    {
        [, , $page] = $this->makeCmsSetup();
        $blocks = $this->makeValidCmsTextBlocksPayload();

        $this->syncService()->sync($page, $blocks);

        $block = $page->cms_blocks()->first();
        $blocks[0]['id'] = $block->id;
        $blocks[0]['state'] = ImplementationCmsBlock::STATE_PUBLIC;

        $this->syncService()->sync($page, $blocks);

        $this->assertSame(ImplementationCmsBlock::STATE_PUBLIC, $block->refresh()->state);

        unset($blocks[0]['state']);

        $this->syncService()->sync($page, $blocks);

        $this->assertSame(ImplementationCmsBlock::STATE_PUBLIC, $block->refresh()->state);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testSyncsMarkdownMediaForParentAndItemValues(): void
    {
        [$identity, , $page] = $this->makeCmsSetup();
        $parentMedia = $this->makeMedia('cms_media', $identity);
        $itemMedia = $this->makeMedia('cms_media', $identity);
        $blocks = $this->makeValidCmsInfoBlocksPayload();

        $blocks[0]['values']['section_description'] = $this->makeMarkdownDescription($parentMedia);
        $blocks[0]['items'][0]['values']['description'] = $this->makeMarkdownDescription($itemMedia);

        $this->syncService()->sync($page, $blocks);

        $block = $page->cms_blocks()->first();
        $item = $block->items()->first();
        $parentValue = $block->values()->where('field_key', 'section_description')->first();
        $itemValue = $item->values()->where('field_key', 'description')->first();

        $this->assertInstanceOf(ImplementationCmsBlockValue::class, $parentValue);
        $this->assertInstanceOf(ImplementationCmsBlockItemValue::class, $itemValue);
        $this->assertSame('implementation_cms_block_value', $parentMedia->refresh()->mediable_type);
        $this->assertSame($parentValue->id, $parentMedia->mediable_id);
        $this->assertSame('value', $parentMedia->meta['markdown_column']);
        $this->assertSame('implementation_cms_block_item_value', $itemMedia->refresh()->mediable_type);
        $this->assertSame($itemValue->id, $itemMedia->mediable_id);
        $this->assertSame('value', $itemMedia->meta['markdown_column']);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRemovesMarkdownMediaWhenMarkdownValuesAreRemoved(): void
    {
        [$identity, , $page] = $this->makeCmsSetup();
        $parentMedia = $this->makeMedia('cms_media', $identity);
        $itemMedia = $this->makeMedia('cms_media', $identity);
        $blocks = $this->makeValidCmsInfoBlocksPayload();

        $blocks[0]['values']['section_description'] = $this->makeMarkdownDescription($parentMedia);
        $blocks[0]['items'][0]['values']['description'] = $this->makeMarkdownDescription($itemMedia);

        $this->syncService()->sync($page, $blocks);

        $block = $page->cms_blocks()->first();
        $item = $block->items()->first();

        $this->syncService()->sync($page, [[
            'id' => $block->id,
            'block_type_key' => InfoCmsBlockConfig::KEY,
            'values' => [
                'section_title' => 'Section title',
                'blocks_per_row' => 3,
            ],
            'items' => [[
                'id' => $item->id,
                'item_type_key' => InfoCmsBlockConfig::ITEM_TYPE_POST,
                'values' => [
                    'title' => 'First post',
                    'button_enabled' => false,
                ],
            ]],
        ]]);

        $this->assertDatabaseMissing('media', ['id' => $parentMedia->id]);
        $this->assertDatabaseMissing('media', ['id' => $itemMedia->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testUpdatesValuesReordersPostsRemovesPostsAndClearsMedia(): void
    {
        [$identity, , $page] = $this->makeCmsSetup();
        $media = $this->makeMedia('implementation_block_media', $identity);
        $this->syncService()->sync($page, $this->makeValidCmsInfoBlocksPayload($media->uid));

        $block = $page->cms_blocks()->first();
        $items = $block->items()->get();
        $removedItemId = $items[0]->id;
        $keptItemId = $items[1]->id;

        $this->syncService()->sync($page, [[
            'id' => $block->id,
            'block_type_key' => InfoCmsBlockConfig::KEY,
            'values' => [
                'section_title' => 'Updated section title',
                'blocks_per_row' => 2,
            ],
            'items' => [[
                'id' => $keptItemId,
                'item_type_key' => InfoCmsBlockConfig::ITEM_TYPE_POST,
                'values' => [
                    'media' => null,
                    'title' => 'Updated second post',
                    'description' => 'Updated second description',
                    'button_enabled' => false,
                ],
            ]],
        ]]);

        $block->refresh();
        $keptItem = $block->items()->first();

        $this->assertSame('Updated section title', $this->blockValue($block, 'section_title'));
        $this->assertSame('2', $this->blockValue($block, 'blocks_per_row'));
        $this->assertNull($this->blockValue($block, 'section_description'));
        $this->assertSame(1, $block->items()->count());
        $this->assertSame($keptItemId, $keptItem->id);
        $this->assertSame(0, $keptItem->order);
        $this->assertSame('Updated second post', $this->itemValue($keptItemId, 'title'));
        $this->assertNull($this->itemValue($keptItemId, 'media'));
        $this->assertDatabaseMissing('implementation_cms_block_items', ['id' => $removedItemId]);
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testNullPayloadLeavesExistingCmsBlocksUntouched(): void
    {
        [, , $page] = $this->makeCmsSetup();
        $this->syncService()->sync($page, $this->makeValidCmsInfoBlocksPayload());
        $block = $page->cms_blocks()->first();

        $this->syncService()->sync($page, null);
        $this->assertDatabaseHas('implementation_cms_blocks', ['id' => $block->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testExistingCmsBlockWithMissingConfigOnlyUpdatesOrder(): void
    {
        [$identity, , $page] = $this->makeCmsSetup();
        $media = $this->makeMedia('implementation_block_media', $identity);
        $block = $page->cms_blocks()->create([
            'block_type_key' => 'removed_block_type',
            'order' => 5,
            'state' => ImplementationCmsBlock::STATE_PUBLIC,
        ]);
        $blockValue = $block->values()->create([
            'field_key' => 'removed_field',
            'value' => 'Original block value',
        ]);
        $item = $block->items()->create([
            'item_type_key' => 'removed_item_type',
            'order' => 0,
        ]);
        $item->values()->create([
            'field_key' => 'removed_item_field',
            'value' => 'Original item value',
        ]);
        $blockValue->syncMedia([$media->uid], 'implementation_block_media');

        $this->syncService()->sync($page, [[
            'id' => $block->id,
            'block_type_key' => $block->block_type_key,
            'state' => ImplementationCmsBlock::STATE_DRAFT,
            'values' => [
                'removed_field' => 'Changed block value',
            ],
            'items' => [],
        ]]);

        $block->refresh();

        $this->assertSame(0, $block->order);
        $this->assertSame(ImplementationCmsBlock::STATE_PUBLIC, $block->state);
        $this->assertSame('Original block value', $this->blockValue($block, 'removed_field'));
        $this->assertSame(1, $block->items()->count());
        $this->assertSame('Original item value', $this->itemValue($item->id, 'removed_item_field'));
        $this->assertSame($blockValue->id, $media->refresh()->mediable_id);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testEmptyPayloadRemovesCmsBlocksWithoutTouchingLegacyPageBlocks(): void
    {
        [, , $page] = $this->makeCmsSetup();
        $this->syncService()->sync($page, $this->makeValidCmsInfoBlocksPayload());
        $block = $page->cms_blocks()->first();

        $pageBlockItem = $page->blocks()->create([
            'key' => 'page_block',
            'type' => 'text',
            'title' => 'Page block',
            'description' => 'Page block description',
            'order' => 0,
        ]);

        $this->syncService()->sync($page, []);

        $this->assertDatabaseMissing('implementation_cms_blocks', ['id' => $block->id]);
        $this->assertDatabaseHas('implementation_blocks', ['id' => $pageBlockItem->id]);
        $this->assertSame(1, ImplementationBlock::query()->where('implementation_page_id', $page->id)->count());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRemovingBlockDeletesChildrenAndMediaButKeepsTranslationUsage(): void
    {
        [$identity, $implementation, $page] = $this->makeCmsSetup();
        $regularMedia = $this->makeMedia('implementation_block_media', $identity);
        $parentMarkdownMedia = $this->makeMedia('cms_media', $identity);
        $itemMarkdownMedia = $this->makeMedia('cms_media', $identity);
        $blocks = $this->makeValidCmsInfoBlocksPayload($regularMedia->uid);

        $blocks[0]['values']['section_description'] = $this->makeMarkdownDescription($parentMarkdownMedia);
        $blocks[0]['items'][0]['values']['description'] = $this->makeMarkdownDescription($itemMarkdownMedia);

        $this->syncService()->sync($page, $blocks);

        $block = $page->cms_blocks()->first();
        $itemIds = $block->items()->pluck('id')->all();
        $blockValue = $block->values()->where('field_key', 'section_description')->firstOrFail();
        $translationValue = $blockValue->translation_values()->create([
            'key' => 'value',
            'from' => $blockValue->value,
            'from_length' => mb_strlen($blockValue->value),
            'to' => 'Translated value',
            'to_length' => mb_strlen('Translated value'),
            'locale' => 'en-US',
            'implementation_id' => $implementation->id,
            'organization_id' => $implementation->organization_id,
        ]);

        $medias = collect([$regularMedia, $parentMarkdownMedia, $itemMarkdownMedia]);
        $medias->each(fn (Media $media) => $media->load('presets'));
        $presets = $medias->flatMap(fn (Media $media) => $media->presets);

        $this->assertNotEmpty($presets);
        $presets->each(fn ($preset) => $this->assertTrue($preset->fileExists()));

        $usageBefore = TranslationValue::getUsage(
            $implementation->organization_id,
            now(),
            now(),
        )['total']['symbols'];

        $this->syncService()->sync($page, []);

        $this->assertDatabaseMissing('implementation_cms_blocks', ['id' => $block->id]);
        $this->assertDatabaseMissing('implementation_cms_block_values', [
            'implementation_cms_block_id' => $block->id,
        ]);

        foreach ($itemIds as $itemId) {
            $this->assertDatabaseMissing('implementation_cms_block_items', ['id' => $itemId]);
            $this->assertDatabaseMissing('implementation_cms_block_item_values', [
                'implementation_cms_block_item_id' => $itemId,
            ]);
        }

        $medias->each(fn (Media $media) => $this->assertDatabaseMissing('media', ['id' => $media->id]));
        $presets->each(function ($preset) {
            $this->assertDatabaseMissing('media_presets', ['id' => $preset->id]);
            $this->assertFalse($preset->fileExists());
        });

        $this->assertDatabaseHas('translation_values', ['id' => $translationValue->id]);
        $this->assertSame(
            $usageBefore,
            TranslationValue::getUsage($implementation->organization_id, now(), now())['total']['symbols'],
        );
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRemovingOneBlockKeepsTheOtherBlockAndItsValues(): void
    {
        [, , $page] = $this->makeCmsSetup();
        $blocks = $this->makeValidCmsInfoBlocksPayload();
        $blocks[] = $this->makeValidCmsInfoBlocksPayload()[0];
        $blocks[1]['values']['section_title'] = 'Kept section title';

        $this->syncService()->sync($page, $blocks);

        [$removedBlock, $keptBlock] = $page->cms_blocks()->get();
        $blocks[1]['id'] = $keptBlock->id;

        $this->syncService()->sync($page, [$blocks[1]]);

        $this->assertDatabaseMissing('implementation_cms_blocks', ['id' => $removedBlock->id]);
        $this->assertDatabaseHas('implementation_cms_blocks', ['id' => $keptBlock->id]);
        $this->assertSame('Kept section title', $this->blockValue($keptBlock, 'section_title'));
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testSupportsMultipleInfoBlocksOnTheSamePage(): void
    {
        [, , $page] = $this->makeCmsSetup();
        $blocks = $this->makeValidCmsInfoBlocksPayload();
        $blocks[] = $this->makeValidCmsInfoBlocksPayload()[0];
        $blocks[1]['values']['section_title'] = 'Second section title';

        $this->syncService()->sync($page, $blocks);

        $pageBlocks = $page->cms_blocks()->get();

        $this->assertSame([0, 1], $pageBlocks->pluck('order')->all());
        $this->assertSame(
            [InfoCmsBlockConfig::KEY, InfoCmsBlockConfig::KEY],
            $pageBlocks->pluck('block_type_key')->all(),
        );
        $this->assertSame(
            'Second section title',
            $this->blockValue($pageBlocks[1], 'section_title'),
        );
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRejectsCrossPageBlockIdWithoutCreatingAnotherBlock(): void
    {
        [, $implementation, $page] = $this->makeCmsSetup();

        $otherPage = $implementation->pages()->create([
            'page_type' => ImplementationPage::TYPE_PRIVACY,
            'state' => ImplementationPage::STATE_PUBLIC,
            'external' => false,
            'description_position' => ImplementationPage::DESCRIPTION_POSITION_REPLACE,
            'description_alignment' => 'left',
            'blocks_per_row' => 3,
        ]);

        $this->syncService()->sync($page, $this->makeValidCmsInfoBlocksPayload());

        $block = $page->cms_blocks()->first();
        $blocks = $this->makeValidCmsInfoBlocksPayload();
        $blocks[0]['id'] = $block->id;

        $this->assertThrows(
            fn () => $this->syncService()->sync($otherPage, $blocks),
            ModelNotFoundException::class,
        );

        $this->assertSame(0, $otherPage->cms_blocks()->count());
        $this->assertSame(1, $page->cms_blocks()->count());
        $this->assertDatabaseHas('implementation_cms_blocks', ['id' => $block->id]);
    }

    /**
     * @param string $pageType
     * @return array{Identity, Implementation, ImplementationPage}
     */
    protected function makeCmsSetup(string $pageType = ImplementationPage::TYPE_HOME): array
    {
        $identity = $this->makeIdentity();
        $organization = $this->makeTestOrganization($identity);
        $implementation = $this->makeTestImplementation($organization);

        $page = $implementation->pages()->create([
            'page_type' => $pageType,
            'state' => ImplementationPage::STATE_PUBLIC,
            'external' => false,
            'description_position' => ImplementationPage::DESCRIPTION_POSITION_REPLACE,
            'description_alignment' => 'left',
            'blocks_per_row' => 3,
        ]);

        $proxy = $this->makeIdentityProxy($identity);
        request()->headers->set('Authorization', "Bearer $proxy->access_token");

        return [$identity, $implementation, $page];
    }

    /**
     * @param string|null $mediaUid
     * @return array
     */
    protected function makeValidCmsInfoBlocksPayload(?string $mediaUid = null): array
    {
        return [[
            'block_type_key' => InfoCmsBlockConfig::KEY,
            'values' => [
                'section_title' => 'Section title',
                'section_description' => 'Section description',
                'section_background_color' => '#ffffff',
                'blocks_per_row' => 3,
            ],
            'items' => [[
                'item_type_key' => InfoCmsBlockConfig::ITEM_TYPE_POST,
                'values' => [
                    'media' => $mediaUid,
                    'label' => 'Label',
                    'title' => 'First post',
                    'description' => 'First description',
                    'button_enabled' => true,
                    'button_text' => 'Open',
                    'button_link' => 'https://example.com/aanbod',
                    'button_link_label' => 'Open offer',
                    'button_target_blank' => false,
                ],
            ], [
                'item_type_key' => InfoCmsBlockConfig::ITEM_TYPE_POST,
                'values' => [
                    'label' => null,
                    'title' => 'Second post',
                    'description' => 'Second description',
                    'button_enabled' => false,
                ],
            ]],
        ]];
    }

    /**
     * @return array
     */
    protected function makeValidCmsTextBlocksPayload(): array
    {
        return [[
            'block_type_key' => TextCmsBlockConfig::KEY,
            'values' => [
                'section_title' => 'Text section title',
                'section_description' => 'Text section description',
            ],
            'items' => [],
        ]];
    }

    /**
     * @param Media $media
     * @return string
     */
    protected function makeMarkdownDescription(Media $media): string
    {
        return implode("  \n", [
            '# Section',
            '![](' . $media->urlPublic('public') . ')',
            '# Details',
        ]);
    }

    /**
     * @param ImplementationCmsBlock $block
     * @param string $fieldKey
     * @return string|null
     */
    protected function blockValue(ImplementationCmsBlock $block, string $fieldKey): ?string
    {
        return $block->values()->where('field_key', $fieldKey)->value('value');
    }

    /**
     * @param int $itemId
     * @param string $fieldKey
     * @return string|null
     */
    protected function itemValue(int $itemId, string $fieldKey): ?string
    {
        return ImplementationCmsBlockItemValue::query()
            ->where('implementation_cms_block_item_id', $itemId)
            ->where('field_key', $fieldKey)
            ->value('value');
    }

    /**
     * @return ImplementationCmsBlockSyncService
     */
    protected function syncService(): ImplementationCmsBlockSyncService
    {
        return resolve(ImplementationCmsBlockSyncService::class);
    }
}
