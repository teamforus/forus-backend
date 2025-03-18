<?php

namespace App\Services\MediaService;

use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Models\MediaPreset as PresetModel;
use Exception;
use Illuminate\Contracts\Filesystem\Filesystem;

abstract class MediaPreset
{
    /**
     * @var ?string
     */
    public ?string $queue = null;

    /**
     * MediaPreset constructor.
     *
     * @param string $name
     * @param string|null $format
     * @param int $quality
     */
    public function __construct(
        public string $name,
        public ?string $format = 'jpg',
        public int $quality = 75,
    ) {
    }

    /**
     * @param Filesystem $storage
     * @param string $storagePath
     * @param string|null $extension
     * @throws Exception
     * @return string
     */
    public function makeUniquePath(
        Filesystem $storage,
        string $storagePath,
        string $extension = null
    ): string {
        $extension = $extension ?: $this->format;

        return str_start(sprintf(
            '%s/%s.%s',
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
    ): mixed;

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
        Media $media,
    ): mixed;

    /**
     * Returns a unique string that is free to be used as media name.
     *
     * @param Filesystem $storage
     * @param string $path
     * @param string $ext
     * @throws Exception
     * @return string
     */
    protected function makeUniqueFileNme(
        Filesystem $storage,
        string $path,
        string $ext
    ): string {
        do {
            $name = bin2hex(random_bytes(64 / 2));
        } while ($storage->exists(sprintf('%s/%s.%s', $path, $name, $ext)));

        return $name;
    }
}
