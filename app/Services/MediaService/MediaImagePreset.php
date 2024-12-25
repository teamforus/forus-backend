<?php

namespace App\Services\MediaService;

use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Models\MediaPreset;
use Illuminate\Contracts\Filesystem\Filesystem;
use Intervention\Image\ImageManager;

class MediaImagePreset extends \App\Services\MediaService\MediaPreset
{
    /**
     * Preset image width
     * @var int
     */
    public int $width = 1000;

    /**
     * Preset image height
     * @var ?int
     */
    public ?int $height = null;

    /**
     * Keep media aspect ratio
     * @var bool
     */
    public bool $preserve_aspect_ratio = true;

    /**
     * @var bool
     */
    public bool $allow_transparency = false;

    /**
     * @var string
     */
    public string $transparent_bg_color = '#ffffff';

    /**
     * @var bool
     */
    protected bool $use_original = false;

    /**
     * @var bool
     */
    protected bool $upscale = true;

    /**
     * MediaImagePreset constructor.
     * @param string $name
     * @param int $width
     * @param int|null $height
     * @param bool $preserveAspectRatio
     * @param int $quality
     * @param string|null $format
     */
    public function __construct(
        string $name,
        int $width = 1000,
        ?int $height = 1000,
        bool $preserveAspectRatio = true,
        int $quality = 75,
        ?string $format = 'jpeg',
    ) {
        $this->width = $width;
        $this->height = $height;
        $this->preserve_aspect_ratio = $preserveAspectRatio;

        parent::__construct($name, $format, $quality);
    }

    /**
     * @param bool $allow
     * @return $this
     * @noinspection PhpUnused
     */
    public function setTransparency(bool $allow = true): MediaImagePreset
    {
        $this->allow_transparency = $allow;
        return $this;
    }

    /**
     * @param string $hex_color
     * @return $this
     * @noinspection PhpUnused
     */
    public function setTransparencyBgColor(string $hex_color = "#ffffff"): MediaImagePreset
    {
        $this->transparent_bg_color = $hex_color;
        return $this;
    }

    /**
     * @param string $format
     * @return $this
     */
    public function setFormat(string $format = 'jpg'): self
    {
        $this->allow_transparency = $format;
        return $this;
    }

    /**
     * @param bool $preserve_aspect_ratio
     * @return $this
     * @noinspection PhpUnused
     */
    public function setPreserveAspectRatio(bool $preserve_aspect_ratio = true): self
    {
        $this->preserve_aspect_ratio = $preserve_aspect_ratio;
        return $this;
    }

    /**
     * @param bool $upscale
     * @return $this
     */
    public function setUpscale(bool $upscale): self
    {
        $this->upscale = $upscale;

        return $this;
    }

    /**
     * Use original image
     *
     * @param string $name
     * @return MediaImagePreset
     */
    public static function createOriginal(string $name): MediaImagePreset
    {
        return (new self($name))->setUseOriginal(true);
    }

    /**
     * @param string $sourcePath
     * @param Filesystem $storage
     * @param string $storagePath
     * @param Media $media
     * @return \Illuminate\Database\Eloquent\Model|mixed
     * @throws \Exception
     */
    public function makePresetModel(
        string $sourcePath,
        Filesystem $storage,
        string $storagePath,
        Media $media
    ): mixed {
        if ($this->use_original) {
            $outPath = $this->makeUniquePath($storage, $storagePath, $media->ext);
            $storage->put($outPath, file_get_contents($sourcePath), 'public');
        } else {
            $format = $this->format ?: $media->ext;
            $outPath = $this->makeUniquePath($storage, $storagePath, $format);
            $image = ImageManager::gd()->read(file_get_contents($sourcePath));

            $width = $this->upscale ? $this->width : min($this->width, $image->width());
            $height = $this->upscale ? $this->height : min($this->height, $image->height());

            if ($this->preserve_aspect_ratio) {
                $image = $image->scale($width, $height);
            } else {
                $image = $image->cover($width, $height);
            }

            if ($format !== 'image/png' || !$this->allow_transparency) {
                $image = ImageManager::gd()->create(
                    $image->width(),
                    $image->height(),
                )->fill($this->transparent_bg_color)->place($image);
            }

            $storage->put($outPath, $image->encodeByMediaType(
                "image/$format",
                quality: $this->quality)->toFilePointer(),
                'public',
            );
        }

        // media size row create
        return tap($media->presets()->firstOrCreate([
            'key'  => $this->name
        ]))->update([
            'path' => $outPath
        ]);
    }

    /**
     * @param Filesystem $storage
     * @param string $storagePath
     * @param MediaPreset $presetModel
     * @param Media $media
     * @return MediaPreset
     * @throws \Exception
     */
    public function copyPresetModel(
        Filesystem $storage,
        string $storagePath,
        MediaPreset $presetModel,
        Media $media
    ): MediaPreset {
        $format = $this->format ?: $media->ext;
        $outPath = $this->makeUniquePath($storage, $storagePath, $format);
        $storage->copy($presetModel->path, $outPath);

        // media size row create
        return $media->presets()->create([
            'key'   => $presetModel->key,
            'path'  => $outPath
        ]);
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function getUseOriginal(): bool
    {
        return $this->use_original;
    }

    /**
     * @param bool $use_original
     *
     * @return $this
     */
    public function setUseOriginal(bool $use_original): self
    {
        $this->use_original = $use_original;
        return $this;
    }
}