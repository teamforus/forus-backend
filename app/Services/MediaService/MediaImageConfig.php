<?php

namespace App\Services\MediaService;

use App\Services\MediaService\Models\Media;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Intervention\Image\Facades\Image;

/**
 * Class MediaSize
 * @package App\Services\MediaService
 */
abstract class MediaImageConfig extends MediaConfig
{
    /**
     * @var float
     */
    protected $preview_aspect_ratio = 1;

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
            } catch (FileNotFoundException $e) {
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
     * @return mixed
     */
    public function getDominantColor(string $sourcePath)
    {
        $image = Image::make(file_get_contents($sourcePath))->backup();

        // Reduce to single color and then sample
        $color = $image->limitColors(1)->pickColor(0, 0, 'hex');
        $image->destroy();

        return $color;
    }
}