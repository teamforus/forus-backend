<?php

namespace App\Services\FileService;

use App\Services\FileService\Models\File;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;

class FileService
{
    /**
     * @var File $file
     */
    protected $model;

    /**
     * Filesystem driver to use for storage
     * @var string $storageDriver
     */
    protected $storageDriver;

    /**
     * Path to upload files in
     * @var string $storagePath
     */
    protected $storagePath;

    /**
     * FileService constructor.
     *
     */
    public function __construct() {
        $this->model = File::query();
        $this->storagePath = str_start(config('file.storage_path'), '/');
        $this->storageDriver = config('file.filesystem_driver', 'local');
    }

    /**
     * Remove expired and missing from db files
     *
     * @throws \Exception
     */
    public function clear() {
        $this->clearFilesWithDeletedFileable();
        $this->clearExpiredFiles();
        $this->clearStorage();
    }

    /**
     * Delete all files that were assigned, but objects no longer exists
     *
     * @return int
     * @throws \Exception
     */
    public function clearFilesWithDeletedFileable() {
        $deleted = 0;

        $files = $this->model->with('fileable')->whereNotNull(
            'fileable_id'
        )->whereNotNull(
            'fileable_type'
        )->get();

        foreach ($files as $file) {
            if (!$file->fileable) {
                $this->unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Clear file that are created but not assigned to any resource
     *
     * @return int
     * @throws \Exception
     */
    public function clearExpiredFiles() {
        $expiredFiles = $this->getExpired();

        foreach ($expiredFiles as $file) {
            $this->unlink($file);
        }

        return $expiredFiles->count();
    }

    /**
     * Clear files that are missing in database from storage
     *
     * @return int count files deleted
     */
    public function clearStorage() {
        $storage = $this->storage();

        $dbFiles = File::query()->pluck('path')->toArray();
        $storageFiles = collect($storage->allFiles($this->storagePath));

        return $storageFiles->filter(function($file) use ($dbFiles) {
            return !in_array($file, $dbFiles);
        })->each(function($file) use ($storage) {
            $storage->delete($file);
        })->count();
    }

    /**
     * Returns list of expired File Models
     *
     * @param null $identity_address
     * @return Collection
     */
    public function getExpired($identity_address = null) {
        $expiredFiles = $this->model->where(function(Builder $query) {
            return $query
                ->whereNull('fileable_type')
                ->orWhereNull('fileable_id');
        })->where('created_at', '<', Carbon::now()->subMinute(60));

        if ($identity_address) {
            $expiredFiles->where(compact('identity_address'));
        }

        return $expiredFiles->get();
    }

    /**
     * Delete file from db and storage
     *
     * @param File $file
     * @return bool|null
     * @throws \Exception
     */
    public function unlink(File $file) {
        self::deleteFile($this->urlPublic($file->path));

        return $file->delete();
    }

    /**
     * Returns a unique string that is free to be used as file name
     *
     * @param $path
     * @param $ext
     * @return string
     */
    protected function makeUniqueFileName($path, $ext) {
        $tokenGenerator = resolve('token_generator');
        $storage = $this->storage();

        do {
            $name = $tokenGenerator->generate('62');
        } while($storage->exists($path . '/' . $name . '.' . $ext));

        return $name;
    }

    /**
     * @param UploadedFile $file
     * @param string $type
     * @param $identity_address
     * @param string|null $file_name
     * @param string|null $extension
     * @return File
     */
    public function uploadSingle(
        UploadedFile $file,
        string $type,
        $identity_address,
        string $file_name = null,
        string $extension = null
    ) {
        // file info
        $path   = (string) $file;
        $name   = $file_name ?: $file->getClientOriginalName();
        $ext    = $extension ?: $file->getClientOriginalExtension();
        $size   = $file->getSize();

        // do upload
        return $this->doUpload($path, $name, $ext, $type, $size, $identity_address);
    }

    /**
     * @param $path
     * @param $name
     * @param $ext
     * @param $type
     * @param $size
     * @param $identity_address
     * @return File
     */
    protected function doUpload($path, $name, $ext, $type, $size, $identity_address) {
        $model = $this->model->newQuery();
        $storage = $this->storage();

        do {
            $uid = resolve('token_generator')->generate('255');
        } while($model->where(compact('uid'))->count() > 0);

        $uniqueName = $this->makeUniqueFileName($this->storagePath, $ext);
        $filePath = str_start($uniqueName . '.' . $ext, '/');
        $filePath = str_start($this->storagePath . $filePath, '/');

        $storage->put($filePath, file_get_contents($path), 'private');
        $original_name = $name;
        $fileable_type = NULL;
        $fileable_id = NULL;
        $path = $filePath;

        /** @var File $file */
        $file = File::create(compact(
            'uid', 'identity_address', 'original_name', 'path', 'size',
            'fileable_id', 'fileable_type', 'ext', 'type'
        ));

        return $file;
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
    public function download(string $path) {
        return $this->storage()->download(ltrim($path, '/'));
    }

    /**
     * @param string $path
     * @return mixed
     */
    public function deleteFile(string $path) {
        return $this->storage()->delete($path);
    }
}
