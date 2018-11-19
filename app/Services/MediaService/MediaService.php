<?php

namespace App\Services\MediaService;

use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Models\MediaSize;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class MediaService
{
    /**
     * @var Media $model
     */
    protected $model;
    protected $mediable_models;

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

    /**
     * Constructor
     *
     * @param array $mediable_models
     */
    public function __construct(
        array $mediable_models = []
    ) {
        $this->model = Media::getModel();
        $this->mediable_models = collect($mediable_models);
        $this->storageDriver = config('media.filesystem_driver');
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

        $dbFiles = collect(MediaSize::getModel()->pluck('path'));
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
        $tokenGenerator = app()->make('token_generator');
        $storage = $this->storage();

        do {
            $name = $tokenGenerator->generate('62');
        } while($storage->exists($path . '/' . $name . '.' . $ext));

        return $name;
    }

    public function uploadSingle(
        UploadedFile $file,
        $type,
        $identity
    ) {

        // file info
        $path   = (string) $file;
        $name   = $file->getClientOriginalName();
        $ext    = $file->getClientOriginalExtension();

        // get clear name
        $name   = rtrim($name, '.' . $ext);

        // do upload
        return $this->doUpload($path, $name, $ext, $type, $identity);
    }

    protected function doUpload($path, $name, $ext, $type, $identity) {
        $mediaConfig = config('media.sizes.' . $type);
        $mediaSizes = $mediaConfig['size'];

        /** @var Model $model */
        $model = Media::getModel();

        do {
            $uid = app()->make('token_generator')->generate('64');
        } while($model->where(compact('uid'))->count() > 0);

        // media row
        $fields = [
            'uid'               => $uid,
            'identity_address'  => $identity,
            'original_name'     => $name,
            'mediable_id'       => NULL,
            'mediable_type'     => NULL,
            'type'              => $type,
            'ext'               => $ext,
        ];

        $storage = $this->storage();

        // media row create
        /** @var mixed $media */
        if (!$media = Media::create($fields)) {
            return false;
        }
        foreach ($mediaSizes as $mediaSizeKey => $mediaSize) {
            $uniqueName = $this->makeUniqueFileNme($this->storagePath, $ext);

            $filePath = $this->storagePath . $uniqueName . '.' . $ext;

            $storage->put($filePath, file_get_contents($path));

            $mediaSize = [
                'x' => $mediaSize[0],
                'y' => $mediaSize[1]
            ];

            // resize and save image
            $image = app()->make('image')->make(
            /** @var mixed $storage */
                $storage->get($filePath)
            );


            if ($mediaSize) {
                $image = $image->fit($mediaSize['x'], $mediaSize['y']);
            }


            $storage->put($filePath, $image->encode()->encoded, 'public');

            // media size row create
            $media->sizes()->create([
                'key'   => $mediaSizeKey,
                'path'  => $filePath
            ]);
        }

        return $media;
    }

    /**
     * @param string $uid
     * @return Media
     */
    public function findByUid(string $uid) {
        /** @var Media $media */
        $media = $this->model->where('uid', $uid)->first();

        return $media;
    }

    /**
     * Get storage
     * @return \Storage
     */
    private function storage() {
        return app()->make('filesystem')->disk($this->storageDriver);
    }

    /**
     * @param string $path
     * @return mixed
     */
    public function publicUrl(string $path) {
        return $this->storage()->url($path);
    }

    /**
     * @param string $path
     * @return mixed
     */
    public function deleteFile(string $path) {
        return $this->storage()->delete($path);
    }
}