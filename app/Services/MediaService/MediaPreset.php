<?php

namespace App\Services\MediaService;

use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Models\MediaPreset as PresetModel;
use Illuminate\Contracts\Filesystem\Filesystem;

abstract class MediaPreset
{
    /**
     * Preset name
     * @var int
     */
    public $name = null;

    /**
     * Media final quality
     * @var int
     */
    public $quality = 75;

    /**
     * Media preset format
     * @var string
     */
    public $format = 'jpg';

    /**
     * @var null|string
     */
    public $queue = null;

    /**
     * MediaPreset constructor.
     * @param string $name
     * @param string $format
     * @param $quality
     */
    public function __construct(
        string $name,
        ?string $format,
        string $quality
    ) {
        $this->name = $name;
        $this->format = $format;
        $this->quality = $quality;
    }

    /**
     * Returns a unique string that is free to be used as media name
     *
     * @param Filesystem $storage
     * @param string $path
     * @param string $ext
     * @return string
     * @throws \Exception
     */
    protected function makeUniqueFileNme(
        Filesystem $storage,
        string $path,
        string $ext
    ): string {
        do {
            $name = bin2hex(random_bytes(64 / 2));
        } while($storage->exists(sprintf('%s/%s.%s', $path, $name, $ext)));

        return $name;
    }

    /**
     * @param Filesystem $storage
     * @param string $storagePath
     * @param string|null $extension
     * @return string
     * @throws \Exception
     */
    public function makeUniquePath(
        Filesystem $storage,
        string $storagePath,
        string $extension = null
    ): string
    {
        $extension = $extension ?: $this->format;

        return str_start(sprintf(
            "%s/%s.%s",
            $storagePath,
            $this->makeUniqueFileNme($storage, $storagePath, $extension),
            $extension
        ), '/');
    }

    /**
     * @param string $sourcePath
     * @param Filesystem $storage
     * @param string $storagePath
     * @param Media $media
     * @return mixed
     */
    abstract public function makePresetModel(
        string $sourcePath,
        Filesystem $storage,
        string $storagePath,
        Media $media
    );

    /**
     * @param Filesystem $storage
     * @param string $storagePath
     * @param PresetModel $presetModel
     * @param Media $media
     * @return mixed
     */
    abstract public function copyPresetModel(
        Filesystem $storage,
        string $storagePath,
        PresetModel $presetModel,
        Media $media
    );
}