<?php

namespace App\Services\MediaService;

use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Models\MediaSize;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Constraint;

class MediaService
{
    /**
     * @var Media $model
     */
    protected $model;

    /**
     * Filesystem driver to use for storage
     * @var string $storageDriver
     */
    protected $storageDriver;

    /**
     * Path to upload images in
     * @var string $storagePath
     */
    protected $storagePath;

    public static $mediable_map;

    /**
     * MediaService constructor.
     * @param $mediable_map
     */
    public function __construct($mediable_map = []) {
        $this->model = Media::query();
        $this->storagePath = str_start(config('media.storage_path'), '/');
        $this->storageDriver = config('media.filesystem_driver', 'local');

        self::$mediable_map = $mediable_map;
    }

    /**
     * @param Media $media
     * @param $type
     * @return \Illuminate\Database\Eloquent\Model|null|object|static
     */
    public function getSize(Media $media, $type) {
        return $media->sizes()->where('type', $type)->first();
    }

    /**
     * Remove expired and missing from db files
     *
     * @throws \Exception
     */
    public function clear() {
        $this->clearMediasWithDeletedMediable();
        $this->clearExpiredMedias();
        $this->clearStorage();
    }

    /**
     * Delete all media that was assigned, but objects no longer exists
     *
     * @return int
     * @throws \Exception
     */
    public function clearMediasWithDeletedMediable() {
        $deleted = 0;

        $medias = $this->model->with('mediable')->whereNotNull(
            'mediable_id'
        )->whereNotNull(
            'mediable_type'
        )->get();

        foreach ($medias as $media) {
            if (!$media->mediable) {
                $this->unlink($media);
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Clear media that are created but not assigned to any resource
     *
     * @return int
     * @throws \Exception
     */
    public function clearExpiredMedias() {
        $expiredMedias = $this->getExpired();

        foreach ($expiredMedias as $media) {
            $this->unlink($media);
        }

        return $expiredMedias->count();
    }

    /**
     * Clear files that are missing in database from storage
     *
     * @return int count files deleted
     */
    public function clearStorage() {
        $storage = $this->storage();

        $dbFiles = collect(MediaSize::query()->pluck('path'));
        $storageFiles = collect($storage->allFiles($this->storagePath));

        $unusedFiles = $storageFiles->filter(function($file) use ($dbFiles) {
            return $dbFiles->search($file) === false;
        });

        $unusedFiles->each(function($file) use ($storage) {
            $storage->delete($file);
        });

        return $unusedFiles->count();
    }

    /**
     * Returns list of expired Media Models
     *
     * @param null $identity_address
     * @return Collection
     */
    public function getExpired($identity_address = null) {
        $expiredMedias = $this->model->where(function(Builder $query) {
            return $query->whereNull(
                'mediable_type'
            )->orWhereNull(
                'mediable_id'
            );
        })->where(
            'created_at', '<', Carbon::now()->subMinute(60)
        );

        if ($identity_address) {
            $expiredMedias->where('identity_address', $identity_address);
        }

        return $expiredMedias->get();
    }

    /**
     * Delete media from db and storage
     *
     * @param Media $media
     * @return bool|null
     * @throws \Exception
     */
    public function unlink(Media $media) {
        foreach ($media->sizes as $size) {
            /** @var MediaSize $size */
            $size->unlink();
            $size->delete();
        }

        return $media->delete();
    }

    /**
     * Returns a unique string that is free to be used as media name
     *
     * @param $path
     * @param $ext
     * @return string
     */
    protected function makeUniqueFileNme($path, $ext) {
        $tokenGenerator = resolve('token_generator');
        $storage = $this->storage();

        do {
            $name = $tokenGenerator->generate('62');
        } while($storage->exists($path . '/' . $name . '.' . $ext));

        return $name;
    }

    /**
     * @param UploadedFile $file
     * @param $type
     * @param $identity
     * @param $extension
     * @return Media|bool
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function uploadSingle(
        UploadedFile $file,
        $type,
        $identity,
        $extension = false
    ) {
        // file info
        $path   = (string) $file;
        $name   = $file->getClientOriginalName();
        $ext    = $extension ?: $file->getClientOriginalExtension();

        // get clear name
        $name   = rtrim($name, '.' . $ext);

        // do upload
        return $this->doUpload($path, $name, $ext, $type, $identity);
    }

    /**
     * @param $type
     * @param Media $media
     * @param $identity
     * @return Media|bool
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function cloneMedia(
        $type,
        Media $media,
        $identity
    ) {
        if ($media->size_original && $media->size_original->fileExists()) {
            return $this->doUpload(
                $media->size_original->storagePath(),
                $media->original_name,
                $media->ext,
                $type,
                $identity
            );
        }

        return false;
    }

    /**
     * @param string $path
     * @param string $original_name
     * @param string $ext
     * @param string $type
     * @param string $identity_address
     * @return Media|bool
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function doUpload(
        string $path,
        string $original_name,
        string $ext,
        string $type,
        string $identity_address
    ) {
        $mediaConfig = config('media.sizes.' . $type);
        $mediaSizes = $mediaConfig['size'];
        $mediaQuery = Media::query();
        $mediable_type = null;
        $mediable_id = null;

        if (!in_array($ext, ['jpg', 'png'])) {
            $ext = 'jpg';
        }

        do {
            $uid = resolve('token_generator')->generate('64');
        } while($mediaQuery->where(compact('uid'))->count() > 0);

        if (!$media = Media::create(compact(
            'uid', 'identity_address', 'original_name', 'type', 'ext',
            'mediable_id', 'mediable_type'
        ))) {
            return false;
        }

        $storage = $this->storage();

        foreach ($mediaSizes as $mediaSizeKey => $mediaSize) {
            $uniqueName = $this->makeUniqueFileNme($this->storagePath, $ext);
            $filePath = str_start($uniqueName . '.' . $ext, '/');
            $filePath = str_start($this->storagePath . $filePath, '/');
            $storage->put($filePath, file_get_contents($path));

            $mediaSize = [
                'x' => $mediaSize[0],
                'y' => $mediaSize[1],
                'keepRatio' => isset($mediaSize[2]) ? $mediaSize[2] : true,
                'quality' => isset($mediaSize[3]) ? $mediaSize[3] : 75,
            ];

            $image = \Image::make(
                $storage->get($filePath)
            )->backup();

            if ($mediaSize['keepRatio']) {
                $image = $image->resize(
                    $mediaSize['x'],
                    $mediaSize['y'], function (
                    Constraint $constraint
                ) {
                    $constraint->aspectRatio();
                });
            } else {
                $image = $image->fit(
                    $mediaSize['x'],
                    $mediaSize['y']
                );
            }

            if ($ext == 'jpg') {
                $image = \Image::canvas(
                    $image->width(),
                    $image->height(),
                    '#ffffff'
                )->insert($image)->backup();
            }

            $storage->put(
                $filePath,
                $image->encode($ext, $mediaSize['quality'])->encoded,
                'public'
            );

            // media size row create
            $media->sizes()->create([
                'key'   => $mediaSizeKey,
                'path'  => $filePath
            ]);
            $image->reset();
        }

        return $media;
    }

    /**
     * @param string $uid
     * @return Media
     */
    public function findByUid(string $uid = null) {
        /** @var Media $media */
        $media = $this->model->where('uid', $uid)->first();

        return $media;
    }

    /**
     * Get storage
     * @return \Storage
     */
    private function storage() {
        return resolve('filesystem')->disk($this->storageDriver);
    }

    /**
     * @param string $path
     * @return mixed
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
     * @return mixed
     */
    public function deleteFile(string $path) {
        return $this->storage()->delete($path);
    }
}