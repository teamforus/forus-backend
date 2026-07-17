<?php

namespace App\Services\MediaService;

use App\Helpers\Color;
use App\Services\MediaService\Models\Media;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Exceptions\InvalidArgumentException;
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
                    'Could not generate dominant color for media %s, go error: %s',
                    $media->id,
                    $e->getMessage()
                ));
            }
        }
    }

    /**
     * @param string $sourcePath
     * @throws InvalidArgumentException
     * @return string|null
     */
    public function getDominantColor(string $sourcePath): ?string
    {
        $manager = ImageManager::usingDriver(Driver::class);
        $image = $manager->decode($sourcePath);

        // Reduce to single color and then sample
        $color = $image->reduceColors(1)->scaleDown(1, 1)->colorAt(0, 0);
        unset($image);

        return Color::normalizeRgbHex($color->toHex('#'));
    }
}
