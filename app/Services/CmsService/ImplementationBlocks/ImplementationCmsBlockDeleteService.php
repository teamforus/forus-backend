<?php

namespace App\Services\CmsService\ImplementationBlocks;

use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlock;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlockItem;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlockItemValue;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlockValue;
use App\Services\MediaService\Models\Media;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImplementationCmsBlockDeleteService
{
    /**
     * @param ImplementationCmsBlock $block
     * @throws Throwable
     * @return void
     */
    public function deleteBlock(ImplementationCmsBlock $block): void
    {
        $block->items()->get()->each(fn (ImplementationCmsBlockItem $item) => $this->deleteItem($item));
        $block->values()->get()->each(fn (ImplementationCmsBlockValue $value) => $this->deleteValue($value));
        $block->delete();
    }

    /**
     * @param ImplementationCmsBlockItem $item
     * @throws Throwable
     * @return void
     */
    public function deleteItem(ImplementationCmsBlockItem $item): void
    {
        $item->values()->get()->each(fn (ImplementationCmsBlockItemValue $value) => $this->deleteValue($value));
        $item->delete();
    }

    /**
     * @param ImplementationCmsBlockValue|ImplementationCmsBlockItemValue $value
     * @throws Throwable
     * @return void
     */
    public function deleteValue(ImplementationCmsBlockValue|ImplementationCmsBlockItemValue $value): void
    {
        DB::transaction(function () use ($value) {
            $value->loadMissing('medias.presets');

            $mediaPaths = $value->medias
                ->flatMap(fn (Media $media) => $media->presets->pluck('path'))
                ->values()
                ->all();

            $value->medias->each(fn (Media $media) => $media->delete());
            $value->delete();

            DB::afterCommit(fn () => $this->deleteMediaFiles($mediaPaths));
        });
    }

    /**
     * @param string[] $mediaPaths
     * @return void
     */
    protected function deleteMediaFiles(array $mediaPaths): void
    {
        foreach ($mediaPaths as $mediaPath) {
            try {
                if (!resolve('media')->deleteFile($mediaPath)) {
                    Log::warning('Failed to delete CMS block media file after commit', [
                        'path' => $mediaPath,
                    ]);
                }
            } catch (Throwable $exception) {
                Log::error('Failed to delete CMS block media file after commit', [
                    'path' => $mediaPath,
                    'exception' => $exception,
                ]);
            }
        }
    }
}
