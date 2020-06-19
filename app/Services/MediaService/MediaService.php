<?php

namespace App\Services\MediaService;

use App\Services\MediaService\Exceptions\MediaConfigAlreadyRegisteredException;
use App\Services\MediaService\Jobs\RegenerateMediaJob;
use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Models\MediaPreset as PresetModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class MediaService
{
    /**
     * Media model
     * @var Media $model
     */
    protected $model;

    /**
     * Default Filesystem driver to use for storage
     * May be overwritten in media config file
     * @var string $storageDriver
     */
    protected $storageDriver;

    /**
     * Default storage path
     * May be overwritten in media config file
     * @var string $storagePath
     */
    protected $storagePath;

    /**
     * @var array|MediaConfig[]
     */
    protected static $mediaConfigs = [];

    /**
     * @param array $configs
     * @param bool $append
     * @return MediaConfig[]|array
     * @throws MediaConfigAlreadyRegisteredException
     */
    public static function setMediaConfigs(array $configs = [], bool $append = true) {
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
    public static function getMediaConfigs() {
        return self::$mediaConfigs;
    }

    /**
     * @param MediaConfig $mediaConfig
     * @return MediaConfig
     * @throws MediaConfigAlreadyRegisteredException
     */
    public static function addMediaConfig(MediaConfig $mediaConfig) {
        if (isset(self::$mediaConfigs[$mediaConfig->getName()])) {
            throw new MediaConfigAlreadyRegisteredException(sprintf(
                "Media config %s already registered",
                $mediaConfig->getName()
            ));
        }

        return self::$mediaConfigs[$mediaConfig->getName()] = $mediaConfig;
    }

    /**
     * @param string $name
     * @return MediaConfig|mixed|null
     */
    public static function getMediaConfig(string $name) {
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
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Relations\HasMany|object|null
     */
    public function getSize(Media $media, $type) {
        return $media->presets()->where('type', $type)->first();
    }

    /**
     * Remove expired and missing from db files
     *
     * @throws \Exception
     */
    public function clear() {
        $this->clearMediasWithoutMediable();
        $this->clearExpiredMedias();
        $this->clearStorage();
    }

    /**
     * Delete all media with missing mediable
     *
     * @return int
     * @throws \Exception
     */
    public function clearMediasWithoutMediable() {
        $medias = $this->getMediaWithoutMediableList();

        foreach ($medias as $media) {
            $this->unlink($media);
        }

        return $medias->count();
    }

    /**
     * Get all media with missing mediable
     *
     * @return Media[]|Builder[]|Collection|\Illuminate\Database\Query\Builder[]|\Illuminate\Support\Collection
     */
    public function getMediaWithoutMediableList() {
        return $this->model->newQuery()->with('mediable')->whereNotNull(
            'mediable_id'
        )->whereNotNull('mediable_type')->get()->filter(function(Media $media) {
            return is_null($media->mediable);
        });
    }

    /**
     * Clear media that are created but not assigned to any resource
     *
     * @param float|int $minutes_to_expire
     * @return int
     * @throws \Exception
     */
    public function clearExpiredMedias($minutes_to_expire = 5 * 60) {
        $expiredMedias = $this->getExpiredList($minutes_to_expire);

        foreach ($expiredMedias as $media) {
            $this->unlink($media);
        }

        return count($expiredMedias);
    }

    /**
     * Returns list of all files uploaded to storage but not assigned to any entity
     *
     * @param float|int $minutes_to_expire
     * @return Media[]|Builder[]|Collection
     */
    public function getExpiredList(
        $minutes_to_expire = 5 * 60
    ) {
        $expiredMedias = $this->model->newQuery()->where(function(Builder $query) {
            $query->whereNull('mediable_type');
            $query->orWhereNull('mediable_id');
        })->where('created_at', '<', Carbon::now()->subMinutes($minutes_to_expire));

        // query to filter media without user
        return $expiredMedias->get();
    }

    /**
     * Delete all files which exists on storage but are not listed in db
     *
     * @return int count files deleted
     */
    public function clearStorage() {
        $unusedFiles = $this->getUnusedFilesList();
        $storage = $this->storage();

        foreach ($unusedFiles as $unusedFile) {
            $storage->delete($unusedFile);
        }

        return count($unusedFiles);
    }

    /**
     * Make list files which exists on storage but are not listed in db
     *
     * @return array
     */
    public function getUnusedFilesList() {
        $storage = $this->storage();
        $dbFiles = PresetModel::query()->pluck('path');

        return array_filter($storage->allFiles(
            $this->storagePath
        ), function($file) use ($dbFiles) {
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
    public function unlink(Media $media) {
        foreach ($media->presets as $size) {
            $size->unlink();
            $size->delete();
        }

        return $media->delete();
    }

    /**
     * @param string $filePath
     * @param string $fileName
     * @param string $type
     * @param array|null $syncPresets
     * @return Media
     * @throws \Exception
     */
    public function uploadSingle(
        string $filePath,
        string $fileName,
        string $type,
        array $syncPresets = null
    ) {
        return $this->makeMedia(
            TmpFile::fromTmpFile($filePath),
            pathinfo($fileName,PATHINFO_FILENAME),
            pathinfo($fileName,PATHINFO_EXTENSION),
            $type,
            $syncPresets
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
     * @return Media|Builder|\Illuminate\Database\Eloquent\Model
     * @throws \Exception
     */
    protected function makeMediaModel(
        string $original_name,
        string $ext,
        string $type
    ) {
        if (!$media = $this->model->newQuery()->create(array_merge(compact(
            'original_name', 'type', 'ext'
        ), [
            'uid' => self::makeUniqueUid()
        ]))) {
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
    ) {
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
     * @param array $mediaPresets
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
    ) {
        $useQueue = !$fromQueue && $mediaConfig->useQueue();

        if ($useQueue) {
            /** @var MediaPreset[] $mediaPresets */
            $mediaPresets = array_values(array_filter($mediaPresets, function(
                MediaPreset $mediaPreset
            ) use ($mediaConfig, $syncPresets) {
                return in_array(
                    $mediaPreset->name,
                    is_null($syncPresets) ? $mediaConfig->getSyncPresets() : array_merge([
                        $mediaConfig->getRegenerationPresetName()
                    ], $syncPresets)
                );
            }));
        }

        foreach ($mediaPresets as $mediaPreset) {
            $mediaPreset->makePresetModel(
                $tmpFile->path(), $this->storage(), $this->storagePath, $media
            );
        }

        if ($useQueue) {
            RegenerateMediaJob::dispatch($mediaConfig, $media)->onQueue(
                config('media.queue_name')
            );
        }

        $mediaConfig->onMediaPresetsUpdated($media, $fromQueue);
        $tmpFile->close();

        return $media;
    }

    /**
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function regenerateAllMedia() {
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
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function regenerateMedia(
        MediaConfig $mediaConfig,
        Media $media = null,
        bool $fromQueue = false
    ): void {
        $sourcePresetName = $mediaConfig->getRegenerationPresetName();
        $medias = $this->model->newQuery()->where([
            'type' => $mediaConfig->getName()
        ]);

        if ($media) {
            $medias->where('id', $media->id);
        }

        $newPresets = array_filter($mediaConfig->getPresets(), static function(
            MediaPreset $mediaPreset
        ) use ($sourcePresetName) {
            return $mediaPreset->name !== $sourcePresetName;
        });

        $newPresetsKeys = array_map(static function(MediaPreset $mediaPreset) {
            return $mediaPreset->name;
        }, $newPresets);

        foreach ($medias->get() as $mediaModel) {
            $source = $mediaModel->findPreset($sourcePresetName);

            if (!$source) {
                throw new \RuntimeException(sprintf(join([
                    "Could not regenerate files for media \"%s\".\n",
                    "Source preset \"%s\" is missing.\n"
                ]), $mediaModel->id, $sourcePresetName));
            }

            /** @var PresetModel[] $mediaSizes */
            $presetModels = $mediaModel->presets()->where(
                'key', '!=', $sourcePresetName
            )->get();

            foreach ($presetModels as $presetModel) {
                $presetModel->unlink();

                if (!in_array($presetModel->key, $newPresetsKeys, true)) {
                    $presetModel->delete();
                }
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
     * @return Media|bool|Builder|\Illuminate\Database\Eloquent\Model
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException|\Exception
     */
    public function cloneMedia(
        Media $media,
        string $type = null,
        bool $forceRegenerate = false
    ) {
        $type = $type ?? $media->type;
        $copyFiles = $type === $media->type;

        if (!$forceRegenerate && $copyFiles) {
            return $this->cloneMediaCopy($media);
        }

        return $this->cloneMediaGenerate($media, $type);
    }

    /**
     * @param Media $media
     * @return Media|bool|\Illuminate\Database\Eloquent\Model
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException|\Exception
     */
    protected function cloneMediaCopy(Media $media) {
        $oldMediaConfig = self::getMediaConfig($media->type);
        $source = $media->findPreset($oldMediaConfig->getRegenerationPresetName());

        return $this->makeMedia(
            new TmpFile($this->storage()->get($source->path)),
            $media->original_name,
            $media->ext,
            $media->type
        );
    }

    /**
     * @param Media $media
     * @param string $type
     * @return Media|Builder|\Illuminate\Database\Eloquent\Model
     * @throws \Exception
     */
    protected function cloneMediaGenerate(Media $media, string $type) {
        $mediaConfig = self::getMediaConfig($type);
        $mediaPresets = $mediaConfig->getPresets();
        $newMedia = $this->makeMediaModel(
            $media->original_name, $media->ext, $type
        );

        foreach ($mediaPresets as $mediaPreset) {
            /** @var PresetModel $presetModel */
            if ($presetModel = $media->presets()->where([
                'key' => $mediaPreset->name
            ])->first()) {
                $mediaPreset->copyPresetModel(
                    $this->storage(),
                    $this->storagePath,
                    $presetModel,
                    $newMedia
                );
            }
        }

        return $newMedia;
    }

    /**
     * @param string $uid
     * @return Media
     */
    public function findByUid(string $uid = null) {
        /** @var Media $media */
        $media = $this->model->newQuery()->where('uid', $uid)->first();

        return $media;
    }

    /**
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    private function storage() {
        return resolve('filesystem')->disk($this->storageDriver);
    }

    /**
     * @param string $path
     * @return mixeds
     */
    public function urlPublic(string $path) {
        return $this->storage()->url(ltrim($path, '/'));
    }

    /**
     * @param string $path
     * @return mixed
     */
    public function path(string $path) {
        return $this->storage()->path($path);
    }

    /**
     * @param string $path
     * @return string|null
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException|null
     */
    public function getContent(string $path) {
        return $this->storageFileExists($path) ? $this->storage()->get($path) : null;
    }

    /**
     * @param string $path
     * @return bool
     */
    public function storageFileExists(string $path) {
        return $this->storage()->exists($path);
    }

    /**
     * @param string $path
     * @return bool
     */
    public function deleteFile(string $path) {
        return $this->storage()->delete($path);
    }
}