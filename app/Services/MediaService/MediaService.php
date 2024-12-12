<?php

namespace App\Services\MediaService;

use App\Services\MediaService\Jobs\RegenerateMediaJob;
use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Models\MediaPreset as PresetModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;

class MediaService
{
    /**
     * Media model
     * @var Media|Model $model
     */
    protected Model|Media $model;

    /**
     * Default Filesystem driver to use for storage
     * May be overwritten in media config file
     * @var string $storageDriver
     */
    protected mixed $storageDriver;

    /**
     * Default storage path
     * May be overwritten in media config file
     * @var string $storagePath
     */
    protected string $storagePath;

    /**
     * @var array|MediaConfig[]
     */
    protected static array $mediaConfigs = [];

    /**
     * @param array $configs
     * @param bool $append
     * @return MediaConfig[]|array
     */
    public static function setMediaConfigs(array $configs = [], bool $append = true): array
    {
        if (!$append) {
            self::$mediaConfigs = [];
        }

        foreach ($configs as $config) {
            self::addMediaConfig($config);
        }

        return self::getMediaConfigs();
    }

    /**
     * @return MediaConfig[]|array
     */
    public static function getMediaConfigs(): array
    {
        return self::$mediaConfigs;
    }

    /**
     * @param MediaConfig $mediaConfig
     * @return MediaConfig
     */
    public static function addMediaConfig(MediaConfig $mediaConfig): MediaConfig
    {
        return self::$mediaConfigs[$mediaConfig->getName()] = $mediaConfig;
    }

    /**
     * @param string $name
     * @return MediaConfig|mixed|null
     */
    public static function getMediaConfig(string $name): mixed
    {
        return self::$mediaConfigs[$name] ?? null;
    }

    /**
     * MediaService constructor.
     */
    public function __construct() {
        $this->model = new Media();
        $this->storagePath = str_start(config('media.storage_path'), '/');
        $this->storageDriver = config('media.filesystem_driver', 'local');
    }

    /**
     * @param Media $media
     * @param $type
     * @return PresetModel|Model|null
     */
    public function getSize(Media $media, $type): PresetModel|Model|null
    {
        return $media->presets()->where('type', $type)->first();
    }

    /**
     * Remove expired and missing from db files
     *
     * @throws \Exception
     */
    public function clear(): void
    {
        $this->clearMediasWithoutMediable();
        $this->clearExpiredMedias();
        $this->clearStorage();
    }

    /**
     * Delete all media with missing mediable
     *
     * @return int count media removed
     * @throws \Exception
     */
    public function clearMediasWithoutMediable(): int
    {
        return $this
            ->getMediaWithoutMediableList()
            ->each(fn (Media $media) => $this->unlink($media))
            ->count();
    }

    /**
     * Get all media with missing mediable
     *
     * @return Media[]|Builder[]|Collection|SupportCollection
     */
    public function getMediaWithoutMediableList(): array|Collection|SupportCollection
    {
        return $this->model
            ->newQuery()
            ->with('mediable')
            ->whereNotNull('mediable_id')
            ->whereNotNull('mediable_type')
            ->get()
            ->filter(fn (Media $media) => is_null($media->mediable));
    }

    /**
     * Clear media that are created but not assigned to any resource
     *
     * @param float|int $minutesToExpire
     * @return int
     * @throws \Exception
     */
    public function clearExpiredMedias(float|int $minutesToExpire = 5 * 60): int
    {
        return $this
            ->getExpiredList($minutesToExpire)
            ->each(fn (Media $media) => $this->unlink($media))
            ->count();
    }

    /**
     * Returns list of all files uploaded to storage but not assigned to any entity
     *
     * @param float|int $minutesToExpire
     * @return Media[]|Builder[]|Collection
     */
    public function getExpiredList(float|int $minutesToExpire = 5 * 60): Collection|array
    {
        $expiredMedias = $this->model->newQuery()->where(function(Builder $query) {
            $query->whereNull('mediable_type');
            $query->orWhereNull('mediable_id');
        })->where('created_at', '<', Carbon::now()->subMinutes($minutesToExpire));

        // query to filter media without user
        return $expiredMedias->get();
    }

    /**
     * Delete all files which exists on storage but are not listed in db
     *
     * @return int count files deleted
     */
    public function clearStorage(): int
    {
        $storage = $this->storage();

        return collect($this->getUnusedFilesList())
            ->each(fn (string $filePath) => $storage->delete($filePath))
            ->count();
    }

    /**
     * Make list files which exists on storage but are not listed in db
     *
     * @return array
     */
    public function getUnusedFilesList(): array
    {
        $storage = $this->storage();
        $dbFiles = PresetModel::query()->pluck('path');

        return array_filter($storage->allFiles($this->storagePath), function($file) use ($dbFiles) {
            return $dbFiles->search(str_start($file, '/')) === false;
        });
    }

    /**
     * Delete media from db and storage
     *
     * @param Media $media
     * @return bool|null
     * @throws \Exception
     */
    public function unlink(Media $media): ?bool
    {
        foreach ($media->presets as $size) {
            $size->unlink();
            $size->delete();
        }

        return $media->delete();
    }

    /**
     * @param string|TmpFile $filePath
     * @param string $fileName
     * @param string $type
     * @param array|string|null $syncPresets
     * @return Media
     * @throws \Exception
     */
    public function uploadSingle(
        string|TmpFile $filePath,
        string $fileName,
        string $type,
        array|string|null $syncPresets = null
    ): Media {
        return $this->makeMedia(
            $filePath instanceof TmpFile ? $filePath :  TmpFile::fromTmpFile($filePath),
            pathinfo($fileName,PATHINFO_FILENAME),
            pathinfo($fileName,PATHINFO_EXTENSION),
            $type,
            $syncPresets !== null ? (array) $syncPresets : null,
        );
    }

    /**
     * @return string
     * @throws \Exception
     */
    protected function makeUniqueUid(): string
    {
        do {
            $uid = bin2hex(random_bytes(64 / 2));
        } while($this->model->newQuery()->where(compact('uid'))->exists());

        return $uid;
    }

    /**
     * @param string $original_name
     * @param string $ext
     * @param string $type
     * @return Media
     * @throws \Exception
     */
    protected function makeMediaModel(string $original_name, string $ext, string $type): Media
    {
        $uid = self::makeUniqueUid();
        $media = compact('original_name', 'type', 'ext', 'uid');

        if (!$media = $this->model->newQuery()->create($media)) {
            throw new \Exception("Could not create media model.");
        }

        return $media;
    }

    /**
     * @param TmpFile $path
     * @param string $original_name
     * @param string $ext
     * @param string $type
     * @param array|null $syncPresets
     * @return Media
     * @throws \Exception
     */
    protected function makeMedia(
        TmpFile $path,
        string $original_name,
        string $ext,
        string $type,
        array $syncPresets = null
    ): Media {
        return $this->makeMediaPresets(
            $this->makeMediaModel($original_name, $ext, $type),
            self::getMediaConfig($type),
            self::getMediaConfig($type)->getPresets(),
            $path,
            $syncPresets
        );
    }

    /**
     * @param Media $media
     * @param MediaConfig $mediaConfig
     * @param array|MediaPreset[] $mediaPresets
     * @param TmpFile $tmpFile
     * @param array|null $syncPresets
     * @param bool $fromQueue
     * @return Media
     */
    public function makeMediaPresets(
        Media $media,
        MediaConfig $mediaConfig,
        array $mediaPresets,
        TmpFile $tmpFile,
        array $syncPresets = null,
        bool $fromQueue = false
    ): Media {
        $useQueue = !$fromQueue && $mediaConfig->useQueue();

        if ($useQueue) {
            $syncPresets = is_null($syncPresets) ? $mediaConfig->getSyncPresets() : array_merge([
                $mediaConfig->getRegenerationPresetName()
            ], $syncPresets);

            $mediaPresets = array_values(array_filter($mediaPresets, function(
                MediaPreset $mediaPreset
            ) use ($syncPresets) {
                return in_array($mediaPreset->name, $syncPresets);
            }));
        }

        foreach ($mediaPresets as $mediaPreset) {
            $mediaPreset->makePresetModel(
                $tmpFile->path(), $this->storage(), $this->storagePath, $media
            );
        }

        if ($useQueue) {
            RegenerateMediaJob::dispatch($mediaConfig, $media, $mediaPresets)->onQueue(
                config('media.queue_name')
            );
        }

        $mediaConfig->onMediaPresetsUpdated($media, $fromQueue);
        $tmpFile->close();

        return $media;
    }

    /**
     * @return void
     * @throws \Exception
     * @noinspection PhpUnused
     */
    public function regenerateAllMedia(): void
    {
        foreach (self::getMediaConfigs() as $mediaConfig) {
            if ($mediaConfig->getPresets()[
                $mediaConfig->getRegenerationPresetName()] ?? false) {
                $this->regenerateMedia($mediaConfig);
            }
        }
    }

    /**
     * @param MediaConfig $mediaConfig
     * @param Media|null $media
     * @param bool $fromQueue
     * @param array $keepPresets
     * @param callable|null $callback
     */
    public function regenerateMedia(
        MediaConfig $mediaConfig,
        Media $media = null,
        bool $fromQueue = false,
        array $keepPresets = [],
        ?callable $callback = null,
    ): void {
        $sourcePresetName = $mediaConfig->getRegenerationPresetName();
        $medias = $this->model->newQuery()->where([
            'type' => $mediaConfig->getName()
        ]);

        if ($media) {
            $medias->where('id', $media->id);
        }

        $keepPresetsKeys = array_map(function(MediaPreset $preset) {
            return $preset->name;
        }, $keepPresets);

        $keepPresetsKeys[] = $sourcePresetName;

        $newPresets = array_filter($mediaConfig->getPresets(), function(
            MediaPreset $preset
        ) use ($keepPresetsKeys) {
            return !in_array($preset->name, $keepPresetsKeys);
        });

        $newPresetsKeys = array_map(static function(MediaPreset $mediaPreset) {
            return $mediaPreset->name;
        }, $newPresets);

        $mediaModels = $medias->get();

        foreach ($mediaModels as $index =>  $mediaModel) {
            if ($callback) {
                $callback($mediaModels->count(), $index + 1);
            }

            $source = $mediaModel->findPreset($sourcePresetName);

            if (!$source) {
                throw new \RuntimeException(sprintf(join([
                    "Could not regenerate files for media \"%s\".\n",
                    "Source preset \"%s\" is missing.\n"
                ]), $mediaModel->id, $sourcePresetName));
            }

            /** @var \App\Services\MediaService\Models\MediaPreset[] $presetModels */
            $presetModels = $mediaModel->presets()->whereIn('key', $newPresetsKeys)->get();

            foreach ($presetModels as $presetModel) {
                $presetModel->unlink();
                $presetModel->delete();
            }

            $this->makeMediaPresets($mediaModel, $mediaConfig, $newPresets, new TmpFile(
                $this->storage()->get($source->path)
            ), null, $fromQueue);
        }
    }

    /**
     * @param Media $media
     * @param string|null $type
     * @param bool $forceRegenerate
     * @param array|null $syncPresets
     * @return Media
     * @throws \Throwable
     */
    public function cloneMedia(
        Media $media,
        string $type = null,
        bool $forceRegenerate = false,
        array $syncPresets = null
    ): Media {
        $type = $type ?? $media->type;
        $copyFiles = $type === $media->type;

        if ($copyFiles && !$forceRegenerate) {
            return $this->cloneMediaCopy($media, $type);
        }

        return $this->cloneMediaGenerate($media, $type, $syncPresets);
    }

    /**
     * @param Media $media
     * @param string|null $type
     * @param array|null $syncPresets
     * @return Media
     * @throws \Exception
     */
    protected function cloneMediaGenerate(
        Media $media,
        ?string $type = null,
        array $syncPresets = null
    ): Media {
        $oldMediaConfig = self::getMediaConfig($media->type);
        $source = $media->findPreset($oldMediaConfig->getRegenerationPresetName());
        $file = new TmpFile($this->storage()->get($source->path));
        $type = $type ?: $media->type;

        return $this->makeMedia($file, $media->original_name, $media->ext, $type, $syncPresets);
    }

    /**
     * @param Media $media
     * @param string|null $type
     * @return Media
     * @throws \Exception
     */
    protected function cloneMediaCopy(Media $media, ?string $type = null): Media
    {
        $type = $type ?: $media->type;
        $mediaConfig = self::getMediaConfig($type);
        $mediaPresets = $mediaConfig->getPresets();
        $newMedia = $this->makeMediaModel($media->original_name, $media->ext, $type);

        foreach ($mediaPresets as $mediaPreset) {
            /** @var PresetModel $presetModel */
            if ($presetModel = $media->presets()->where('key', $mediaPreset->name)->first()) {
                $mediaPreset->copyPresetModel(
                    $this->storage(),
                    $this->storagePath,
                    $presetModel,
                    $newMedia
                );
            }
        }

        $mediaConfig->onMediaPresetsUpdated($newMedia);

        return $newMedia;
    }

    /**
     * @param string|null $uid
     * @return Media|null
     */
    public function findByUid(string $uid = null): ?Media
    {
        return $this->model->newQuery()->where('uid', $uid)->first();
    }

    /**
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    public function storage(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return resolve('filesystem')->disk($this->storageDriver);
    }

    /**
     * @param string $path
     * @return string
     */
    public function urlPublic(string $path): string
    {
        return $this->storage()->url(ltrim($path, '/'));
    }

    /**
     * @param string $path
     * @return string
     */
    public function path(string $path): string
    {
        return $this->storage()->path($path);
    }

    /**
     * @param string $path
     * @return string|null
     * @throws null
     */
    public function getContent(string $path): ?string
    {
        return $this->storageFileExists($path) ? $this->storage()->get($path) : null;
    }

    /**
     * @param string $path
     * @return bool
     */
    public function storageFileExists(string $path): bool
    {
        return $this->storage()->exists($path);
    }

    /**
     * @param string $path
     * @return bool
     */
    public function deleteFile(string $path): bool
    {
        return $this->storage()->delete($path);
    }
}