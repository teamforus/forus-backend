<?php

namespace App\Services\CmsService\ImplementationBlocks;

use App\Models\ImplementationPage;
use App\Services\CmsService\ImplementationBlocks\Configs\CmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlock;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlockItem;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlockItemValue;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlockValue;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;

class ImplementationCmsBlockSyncService
{
    /**
     * @param ImplementationCmsBlockDeleteService $deleteService
     */
    public function __construct(
        protected ImplementationCmsBlockDeleteService $deleteService,
    ) {
    }

    /**
     * @param ImplementationPage $page
     * @param array|null $blocks
     * @throws Throwable
     * @return void
     */
    public function sync(ImplementationPage $page, ?array $blocks): void
    {
        if ($blocks === null) {
            return;
        }

        DB::transaction(function () use ($page, $blocks) {
            $keptCmsBlockIds = [];

            foreach ($blocks as $order => $cmsBlockPayload) {
                $cmsBlock = $this->syncBlock($page, $cmsBlockPayload, $order);
                $keptCmsBlockIds[] = $cmsBlock->id;
            }

            $page->cms_blocks()
                ->whereNotIn('id', $keptCmsBlockIds)
                ->get()
                ->each(fn (ImplementationCmsBlock $block) => $this->deleteService->deleteBlock($block));
        });
    }

    /**
     * @param ImplementationPage $page
     * @param array $cmsBlockPayload
     * @param int $order
     * @throws Throwable
     * @return ImplementationCmsBlock
     */
    protected function syncBlock(ImplementationPage $page, array $cmsBlockPayload, int $order): ImplementationCmsBlock
    {
        $blockConfig = ImplementationCmsBlockService::getBlockConfig($cmsBlockPayload['block_type_key']);
        $isSupported = $blockConfig?->isAllowedForPageType($page->page_type) ?? false;

        if (isset($cmsBlockPayload['id'])) {
            $cmsBlock = $page->cms_blocks()
                ->where('block_type_key', $cmsBlockPayload['block_type_key'])
                ->findOrFail($cmsBlockPayload['id']);
        } else {
            $cmsBlock = $page->cms_blocks()->make([
                'block_type_key' => $cmsBlockPayload['block_type_key'],
                'state' => ImplementationCmsBlock::STATE_DRAFT,
            ]);
        }

        $cmsBlock->fill([
            'order' => $order,
            ...($isSupported ? [
                'state' => $cmsBlockPayload['state'] ?? $cmsBlock->state,
            ] : []),
        ])->save();

        if (!$isSupported) {
            return $cmsBlock;
        }

        $this->syncBlockValues($cmsBlock, $cmsBlockPayload['values'] ?? []);
        $this->syncItems($cmsBlock, $cmsBlockPayload['items'] ?? []);

        return $cmsBlock;
    }

    /**
     * @param ImplementationCmsBlock $block
     * @param array $values
     * @throws Throwable
     * @return void
     */
    protected function syncBlockValues(ImplementationCmsBlock $block, array $values): void
    {
        $blockConfig = ImplementationCmsBlockService::getBlockConfig($block->block_type_key);
        $fieldKeys = array_keys($values);

        $this->deleteBlockValues($block, $fieldKeys);

        foreach ($values as $fieldKey => $value) {
            $field = $blockConfig?->field($fieldKey);

            /** @var ImplementationCmsBlockValue $blockValue */
            $blockValue = $block->values()->updateOrCreate([
                'field_key' => $fieldKey,
            ], [
                'value' => $this->castValue($field, $value),
            ]);

            $this->syncValueMedia($blockValue, $field, $value);
        }
    }

    /**
     * @param ImplementationCmsBlock $block
     * @param array $items
     * @throws Throwable
     * @return void
     */
    protected function syncItems(ImplementationCmsBlock $block, array $items): void
    {
        $itemIds = array_filter(Arr::pluck($items, 'id'));

        $block->items()
            ->whereNotIn('id', $itemIds)
            ->get()
            ->each(fn (ImplementationCmsBlockItem $item) => $this->deleteService->deleteItem($item));

        foreach ($items as $order => $itemPayload) {
            $item = isset($itemPayload['id']) ?
                $block->items()->findOrFail($itemPayload['id']) :
                $block->items()->make();

            $item->fill([
                'item_type_key' => $itemPayload['item_type_key'],
                'order' => $order,
            ]);

            $item->save();
            $this->syncItemValues($item, $itemPayload['values'] ?? []);
        }
    }

    /**
     * @param ImplementationCmsBlockItem $item
     * @param array $values
     * @throws Throwable
     * @return void
     */
    protected function syncItemValues(ImplementationCmsBlockItem $item, array $values): void
    {
        $this->deleteItemValues($item, array_keys($values));

        foreach ($values as $fieldKey => $value) {
            $field = ImplementationCmsBlockService::getBlockConfig($item->implementation_cms_block->block_type_key)
                ?->itemField($item->item_type_key, $fieldKey);

            /** @var ImplementationCmsBlockItemValue $itemValue */
            $itemValue = $item->values()->updateOrCreate([
                'field_key' => $fieldKey,
            ], [
                'value' => $this->castValue($field, $value),
            ]);

            $this->syncValueMedia($itemValue, $field, $value);
        }
    }

    /**
     * @param ImplementationCmsBlock $block
     * @param string[] $keptFieldKeys
     * @throws Throwable
     * @return void
     */
    protected function deleteBlockValues(ImplementationCmsBlock $block, array $keptFieldKeys): void
    {
        $block->values()
            ->whereNotIn('field_key', $keptFieldKeys)
            ->get()
            ->each(fn (ImplementationCmsBlockValue $value) => $this->deleteService->deleteValue($value));
    }

    /**
     * @param ImplementationCmsBlockItem $item
     * @param string[] $keptFieldKeys
     * @throws Throwable
     * @return void
     */
    protected function deleteItemValues(ImplementationCmsBlockItem $item, array $keptFieldKeys): void
    {
        $item->values()
            ->whereNotIn('field_key', $keptFieldKeys)
            ->get()
            ->each(fn (ImplementationCmsBlockItemValue $value) => $this->deleteService->deleteValue($value));
    }

    /**
     * @param ImplementationCmsBlockValue|ImplementationCmsBlockItemValue $value
     * @param array|null $field
     * @param mixed $rawValue
     * @throws Throwable
     * @return void
     */
    protected function syncValueMedia(
        ImplementationCmsBlockValue|ImplementationCmsBlockItemValue $value,
        ?array $field,
        mixed $rawValue,
    ): void {
        if (($field['type'] ?? null) === CmsBlockConfig::TYPE_MARKDOWN) {
            $value->syncMarkdownMedia('cms_media', 'value');
        }

        if (($field['type'] ?? null) === CmsBlockConfig::TYPE_MEDIA) {
            $value->syncMedia($rawValue ? [$rawValue] : [], $field['media_type']);
        }
    }

    /**
     * @param array|null $field
     * @param mixed $value
     * @return string|null
     */
    protected function castValue(?array $field, mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($field['type'] ?? null) {
            CmsBlockConfig::TYPE_BOOLEAN => $value ? '1' : '0',
            CmsBlockConfig::TYPE_NUMBER => (string) intval($value),
            default => (string) $value,
        };
    }
}
