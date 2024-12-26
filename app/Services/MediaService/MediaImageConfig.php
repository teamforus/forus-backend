<?php

namespace App\Services\MediaService;

use App\Services\MediaService\Models\Media;
use Intervention\Image\ImageManager;
use Throwable;

abstract class MediaImageConfig extends MediaConfig
{
    /**
     * @var float
     */
    protected float $preview_aspect_ratio = 1;

    /**
     * @return float|null
     */
    public function getPreviewAspectRatio(): ?float
    {
        return floatval($this->preview_aspect_ratio) ?? null;
    }

    /**
     * @param Media $media
     * @param bool $fromQueue
     * @return void
     */
    public function onMediaPresetsUpdated(Media $media, bool $fromQueue = false): void
    {
        if (!$fromQueue && $this->save_dominant_color && $media->presets()->count() > 0) {
            try {
                $file = new TmpFile($media->findPreset(
                    $this->getRegenerationPresetName()
                )->getContent());

                $media->update([
                    'dominant_color' => $this->getDominantColor($file->path()),
                ]);

                $file->close();
            } catch (Throwable $e) {
                logger()->error(sprintf(
                    "Could not generate dominant color for media %s, go error: %s",
                    $media->id,
                    $e->getMessage()
                ));
            }
        }
    }

    /**
     * @param string $sourcePath
     * @return string|null
     */
    public function getDominantColor(string $sourcePath): ?string
    {
        $image = ImageManager::gd()->read($sourcePath);

        // Reduce to single color and then sample
        $color = $image->reduceColors(1)->scaleDown(1, 1)->pickColor(0, 0);
        unset($image);

        return $color->toHex('#');
    }
}