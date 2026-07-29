<?php

namespace App\Services\CmsService\ImplementationBlocks\Commands;

use App\Services\CmsService\ImplementationBlocks\ImplementationCmsBlockDeleteService;
use App\Services\CmsService\ImplementationBlocks\ImplementationCmsBlockService;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlockItemValue;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlockValue;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Throwable;

class CleanupStaleBlockValuesCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'cms:cleanup-stale-block-values
                            {--dry-run : Report stale values without deleting them.}
                            {--force : Do not ask for confirmation before deleting.}';

    /**
     * @var string
     */
    protected $description = 'Clean CMS block values that are no longer present in current block configs.';

    /**
     * @throws Throwable
     * @return int
     */
    public function handle(): int
    {
        $staleBlockValues = $this->staleBlockValues();
        $staleItemValues = $this->staleItemValues();
        $skippedBlockValues = $this->skippedBlockValuesCount();
        $skippedItemValues = $this->skippedItemValuesCount();
        $staleValuesCount = $staleBlockValues->count() + $staleItemValues->count();

        $this->line("Stale parent CMS block values: {$staleBlockValues->count()}");
        $this->line("Stale nested CMS block item values: {$staleItemValues->count()}");
        $this->line("Skipped parent values with unknown block types: $skippedBlockValues");
        $this->line("Skipped nested values with unknown block or item types: $skippedItemValues");

        if ($this->option('dry-run')) {
            $this->info('Dry run complete. No values were deleted.');

            return self::SUCCESS;
        }

        if ($staleValuesCount === 0) {
            $this->info('No stale CMS block values found.');

            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('Delete stale CMS block values?')) {
            $this->info('Skipped.');

            return self::SUCCESS;
        }

        $deleteService = resolve(ImplementationCmsBlockDeleteService::class);

        $staleBlockValues->each(fn (ImplementationCmsBlockValue $value) => $deleteService->deleteValue($value));
        $staleItemValues->each(fn (ImplementationCmsBlockItemValue $value) => $deleteService->deleteValue($value));

        $this->info("Deleted $staleValuesCount stale CMS block values.");

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, ImplementationCmsBlockValue>
     */
    protected function staleBlockValues(): Collection
    {
        return ImplementationCmsBlockValue::query()
            ->with('implementation_cms_block')
            ->get()
            ->filter(function (ImplementationCmsBlockValue $value) {
                $block = $value->implementation_cms_block;

                if (!$block) {
                    return false;
                }

                $config = ImplementationCmsBlockService::getBlockConfig(
                    $block->block_type_key,
                );

                return $config && !$config->field($value->field_key);
            })
            ->values();
    }

    /**
     * @return Collection<int, ImplementationCmsBlockItemValue>
     */
    protected function staleItemValues(): Collection
    {
        return ImplementationCmsBlockItemValue::query()
            ->with([
                'implementation_cms_block_item',
                'implementation_cms_block_item.implementation_cms_block',
            ])
            ->get()
            ->filter(function (ImplementationCmsBlockItemValue $value) {
                $item = $value->implementation_cms_block_item;

                if (!$item || !$item->implementation_cms_block) {
                    return false;
                }

                $config = ImplementationCmsBlockService::getBlockConfig(
                    $item->implementation_cms_block->block_type_key,
                );

                return $config &&
                    $config->itemType($item->item_type_key) &&
                    !$config->itemField($item->item_type_key, $value->field_key);
            })
            ->values();
    }

    /**
     * @return int
     */
    protected function skippedBlockValuesCount(): int
    {
        return ImplementationCmsBlockValue::query()
            ->with('implementation_cms_block')
            ->get()
            ->filter(function (ImplementationCmsBlockValue $value) {
                $block = $value->implementation_cms_block;

                return !$block || !ImplementationCmsBlockService::getBlockConfig($block->block_type_key);
            })
            ->count();
    }

    /**
     * @return int
     */
    protected function skippedItemValuesCount(): int
    {
        return ImplementationCmsBlockItemValue::query()
            ->with([
                'implementation_cms_block_item',
                'implementation_cms_block_item.implementation_cms_block',
            ])
            ->get()
            ->filter(function (ImplementationCmsBlockItemValue $value) {
                $item = $value->implementation_cms_block_item;

                if (!$item || !$item->implementation_cms_block) {
                    return true;
                }

                $config = ImplementationCmsBlockService::getBlockConfig(
                    $item->implementation_cms_block->block_type_key,
                );

                return !$config || !$config->itemType($item->item_type_key);
            })
            ->count();
    }
}
