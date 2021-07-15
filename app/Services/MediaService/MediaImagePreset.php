<?php

namespace App\Services\MediaService;

use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Models\MediaPreset;
use Illuminate\Contracts\Filesystem\Filesystem;
use Intervention\Image\Constraint;

class MediaImagePreset extends \App\Services\MediaService\MediaPreset
{
    /**
     * Preset image width
     * @var int
     */
    public $width = 1000;

    /**
     * Preset image height
     * @var int
     */
    public $height = null;

    /**
     * Keep media aspect ratio
     * @var bool
     */
    public $preserve_aspect_ratio = true;

    /**
     * Media preset format
     * Set null to preserve original format
     * @var bool
     */
    public $format = 'jpg';

    /**
     * @var bool
     */
    public $allow_transparency = false;

    /**
     * @var bool
     */
    public $transparent_bg_color = '#ffffff';

    /**
     * @var bool
     */
    protected $use_original = false;

    /**
     * @var bool
     */
    protected $upscale = true;

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
        int $height = 1000,
        bool $preserveAspectRatio = true,
        int $quality = 75,
        ?string $format = 'jpg'
    ) {
        $this->width = $width;
        $this->height = $height;
        $this->preserve_aspect_ratio = $preserveAspectRatio;

        parent::__construct($name, $format, $quality);
    }

    /**
     * @param bool $allow
     * @return $this
     */
    public function setTransparency($allow = true): MediaImagePreset
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
    public function setFormat($format = 'jpg'): self
    {
        $this->allow_transparency = $format;
        return $this;
    }

    /**
     * @param bool $preserve_aspect_ratio
     * @return $this
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
    ) {
        if ($this->use_original) {
            $outPath = $this->makeUniquePath($storage, $storagePath, $media->ext);
            $storage->put($outPath, file_get_contents($sourcePath), 'public');
        } else {
            $format = $this->format ?: $media->ext;
            $outPath = $this->makeUniquePath($storage, $storagePath, $format);
            $image = \Image::make(file_get_contents($sourcePath))->backup();

            $width = $this->upscale ? $this->width : min($this->width, $image->width());
            $height = $this->upscale ? $this->height : min($this->height, $image->height());

            if ($this->preserve_aspect_ratio) {
                $image = $image->resize($width, $height, function (Constraint $constraint) {
                    $constraint->aspectRatio();
                });
            } else {
                $image = $image->fit($width, $height);
            }

            if ($format !== 'png' || !$this->allow_transparency) {
                $image = \Image::canvas(
                    $image->width(),
                    $image->height(),
                    $this->transparent_bg_color
                )->insert($image)->backup();
            }

            $storage->put($outPath, $image->encode(
                $format, $this->quality
            )->encoded, 'public');

            $image->reset();
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
     * @return \Illuminate\Database\Eloquent\Model|MediaPreset
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