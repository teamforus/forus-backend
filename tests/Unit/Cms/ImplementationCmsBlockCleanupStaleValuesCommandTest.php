<?php

namespace Tests\Unit\Cms;

use App\Models\Identity;
use App\Models\Implementation;
use App\Models\ImplementationPage;
use App\Services\CmsService\ImplementationBlocks\Configs\InfoCmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlock;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlockItemValue;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlockValue;
use App\Services\TranslationService\Models\TranslationValue;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use Tests\Traits\MakesCmsMedia;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;

class ImplementationCmsBlockCleanupStaleValuesCommandTest extends TestCase
{
    use DatabaseTransactions;
    use MakesCmsMedia;
    use MakesTestFunds;
    use MakesTestOrganizations;

    protected const string COMMAND = 'cms:cleanup-stale-block-values';

    /**
     * @return void
     */
    public function testCleansStaleParentValuesAndKeepsConfiguredParentValues(): void
    {
        [, , $block] = $this->makeCmsBlockSetup();
        $configuredValue = $block->values()->create([
            'field_key' => 'section_title',
            'value' => 'Current title',
        ]);
        $staleValue = $block->values()->create([
            'field_key' => 'title',
            'value' => 'Old title',
        ]);

        $this->artisan(self::COMMAND, ['--force' => true])->assertExitCode(0);

        $this->assertDatabaseHas('implementation_cms_block_values', ['id' => $configuredValue->id]);
        $this->assertDatabaseMissing('implementation_cms_block_values', ['id' => $staleValue->id]);
    }

    /**
     * @return void
     */
    public function testCleansStaleItemValuesAndKeepsConfiguredItemValues(): void
    {
        [, , $block] = $this->makeCmsBlockSetup();
        $item = $block->items()->create([
            'item_type_key' => InfoCmsBlockConfig::ITEM_TYPE_POST,
            'order' => 0,
        ]);
        $configuredValue = $item->values()->create([
            'field_key' => 'title',
            'value' => 'Post title',
        ]);
        $staleValue = $item->values()->create([
            'field_key' => 'legacy_field',
            'value' => 'Legacy value',
        ]);

        $this->artisan(self::COMMAND, ['--force' => true])->assertExitCode(0);

        $this->assertDatabaseHas('implementation_cms_block_item_values', ['id' => $configuredValue->id]);
        $this->assertDatabaseMissing('implementation_cms_block_item_values', ['id' => $staleValue->id]);
    }

    /**
     * @return void
     */
    public function testSkipsValuesForUnknownBlockAndItemTypes(): void
    {
        [, , $knownBlock] = $this->makeCmsBlockSetup();

        $unknownBlock = $knownBlock->implementation_page->cms_blocks()->create([
            'block_type_key' => 'unknown_block_type',
            'order' => 1,
            'state' => ImplementationCmsBlock::STATE_PUBLIC,
        ]);

        $unknownBlockValue = $unknownBlock->values()->create([
            'field_key' => 'legacy_field',
            'value' => 'Legacy value',
        ]);

        $unknownItem = $knownBlock->items()->create([
            'item_type_key' => 'unknown_item_type',
            'order' => 0,
        ]);

        $unknownItemValue = $unknownItem->values()->create([
            'field_key' => 'legacy_field',
            'value' => 'Legacy value',
        ]);

        $this->artisan(self::COMMAND, ['--force' => true])->assertExitCode(0);

        $this->assertDatabaseHas('implementation_cms_block_values', ['id' => $unknownBlockValue->id]);
        $this->assertDatabaseHas('implementation_cms_block_item_values', ['id' => $unknownItemValue->id]);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testCleansStaleValueMediaAndKeepsTranslationUsageBeforeDeletingValue(): void
    {
        [$identity, , $block] = $this->makeCmsBlockSetup();
        $media = $this->makeMedia('implementation_block_media', $identity);
        $staleValue = $block->values()->create([
            'field_key' => 'legacy_media',
            'value' => $media->uid,
        ]);
        $translationValue = $this->makeTranslationValue($staleValue);

        $staleValue->syncMedia([$media->uid], 'implementation_block_media');

        $this->artisan(self::COMMAND, ['--force' => true])->assertExitCode(0);

        $this->assertDatabaseMissing('implementation_cms_block_values', ['id' => $staleValue->id]);
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        $this->assertDatabaseHas('translation_values', ['id' => $translationValue->id]);
    }

    /**
     * @return void
     */
    public function testKeepsStaleValueTranslationUsageScopedToOriginalOrganization(): void
    {
        $date = Carbon::now()->startOfMonth()->addDay();
        $this->travelTo($date);

        [, $implementation, $block] = $this->makeCmsBlockSetup();
        [, $otherImplementation] = $this->makeCmsBlockSetup();
        $staleValue = $block->values()->create([
            'field_key' => 'legacy_field',
            'value' => 'Old title',
        ]);
        $translationValue = $this->makeTranslationValue($staleValue, $implementation);

        $this->artisan(self::COMMAND, ['--force' => true])->assertExitCode(0);

        $this->assertDatabaseMissing('implementation_cms_block_values', ['id' => $staleValue->id]);
        $this->assertDatabaseHas('translation_values', ['id' => $translationValue->id]);
        $this->assertSame(
            mb_strlen($staleValue->value),
            TranslationValue::getUsage($implementation->organization_id, $date->copy(), $date->copy())['total']['symbols'],
        );
        $this->assertSame(
            0,
            TranslationValue::getUsage(
                $otherImplementation->organization_id,
                $date->copy(),
                $date->copy(),
            )['total']['symbols'],
        );
    }

    /**
     * @return void
     */
    public function testDryRunReportsStaleValuesWithoutDeletingThem(): void
    {
        [, , $block] = $this->makeCmsBlockSetup();
        $staleValue = $block->values()->create([
            'field_key' => 'title',
            'value' => 'Old title',
        ]);

        $this->artisan(self::COMMAND, ['--dry-run' => true])->assertExitCode(0);

        $this->assertDatabaseHas('implementation_cms_block_values', ['id' => $staleValue->id]);
    }

    /**
     * @return array{Identity, Implementation, ImplementationCmsBlock}
     */
    protected function makeCmsBlockSetup(): array
    {
        $identity = $this->makeIdentity();
        $organization = $this->makeTestOrganization($identity);
        $implementation = $this->makeTestImplementation($organization);

        $page = $implementation->pages()->create([
            'page_type' => ImplementationPage::TYPE_HOME,
            'state' => ImplementationPage::STATE_PUBLIC,
            'external' => false,
            'description_position' => ImplementationPage::DESCRIPTION_POSITION_REPLACE,
            'description_alignment' => 'left',
            'blocks_per_row' => 3,
        ]);

        $block = $page->cms_blocks()->create([
            'block_type_key' => InfoCmsBlockConfig::KEY,
            'order' => 0,
            'state' => ImplementationCmsBlock::STATE_PUBLIC,
        ]);

        return [$identity, $implementation, $block];
    }

    /**
     * @param ImplementationCmsBlockValue|ImplementationCmsBlockItemValue $value
     * @param Implementation|null $implementation
     * @return TranslationValue
     */
    protected function makeTranslationValue(
        ImplementationCmsBlockValue|ImplementationCmsBlockItemValue $value,
        ?Implementation $implementation = null,
    ): TranslationValue {
        return $value->translation_values()->create([
            'key' => 'value',
            'from' => $value->value,
            'from_length' => mb_strlen($value->value),
            'to' => 'Translated value',
            'to_length' => mb_strlen('Translated value'),
            'locale' => 'en-US',
            'implementation_id' => $implementation?->id,
            'organization_id' => $implementation?->organization_id,
        ]);
    }
}
