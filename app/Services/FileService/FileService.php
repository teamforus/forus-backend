<?php

namespace App\Services\FileService;

use App\Services\FileService\Models\File;
use Carbon\Carbon;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileService
{
    /**
     * @var File|Builder $file
     */
    protected File|Builder $model;

    /**
     * Filesystem driver to use for storage
     * @var string $storageDriver
     */
    protected string $storageDriver;

    /**
     * Path to upload files in
     * @var string $storagePath
     */
    protected string $storagePath;

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
     * Delete file from db and storage
     *
     * @param File $file
     * @return bool|null
     */
    public function unlink(File $file): ?bool
    {
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
    protected function makeUniqueFileName($path, $ext): string
    {
        $tokenGenerator = resolve('token_generator');

        do {
            $name = $tokenGenerator->generate('62');
        } while($this->storage()->exists($path . '/' . $name . '.' . $ext));

        return $name;
    }

    /**
     * @param UploadedFile $file
     * @param string $type
     * @param array $options
     * @return File
     */
    public function uploadSingle(UploadedFile $file, string $type, array $options = []): File
    {
        return $this->doUpload(
            $file->getRealPath(),
            $file->getClientOriginalName(),
            $file->getClientOriginalExtension(),
            $type,
            $file->getSize(),
            $options,
        );
    }

    /**
     * @param string $file_path
     * @param string $original_name
     * @param string $ext
     * @param string $type
     * @param string $size
     * @param array $options
     * @return File
     */
    protected function doUpload(
        string $file_path,
        string $original_name,
        string $ext,
        string $type,
        string $size,
        array $options = []
    ): File {
        $uid = File::makeUid();
        $name = $this->makeUniqueFileName($this->storagePath, $ext);

        $storagePrefix = Arr::get($options, 'storage_prefix', '');
        $visibility = Arr::get($options, 'visibility', 'private');

        $path = str_start($name . '.' . $ext, '/');
        $path = str_start($this->storagePath . $storagePrefix . $path, '/');

        $this->storage()->put($path, file_get_contents($file_path), $visibility);

        return File::create(compact(
            'uid', 'original_name', 'path', 'size', 'ext', 'type',
        ));
    }

    /**
     * Get storage
     *
     * @return Filesystem
     */
    private function storage(): Filesystem
    {
        return Storage::disk($this->storageDriver);
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
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function download(string $path): StreamedResponse
    {
        return $this->storage()->download(ltrim($path, '/'));
    }

    /**
     * @param string $path
     * @return string|null
     */
    public function getContent(string $path): string|null
    {
        return $this->storage()->get(ltrim($path, '/'));
    }

    /**
     * @param string $path
     * @return bool
     */
    public function deleteFile(string $path): bool
    {
        return $this->storage()->delete($path);
    }

    /**
     * Delete all files with missing fileable
     *
     * @return int count files removed
     * @throws \Exception
     */
    public function clearFilesWithoutFileable(): int
    {
        return $this
            ->getFilesWithoutFileableList()
            ->each(fn (File $file) => $this->unlink($file))
            ->count();
    }

    /**
     * Get all files with missing fileable
     *
     * @return File[]|Builder[]|Collection|SupportCollection
     */
    public function getFilesWithoutFileableList(): array|Collection|SupportCollection
    {
        return $this->model
            ->newQuery()
            ->with('fileable')
            ->whereNotNull('fileable_id')
            ->whereNotNull('fileable_type')
            ->get()
            ->filter(fn (File $file) => is_null($file->fileable));
    }

    /**
     * Clear files that are created but not assigned to any resource
     *
     * @param float|int $minutesToExpire
     * @return int
     * @throws \Exception
     */
    public function clearExpiredFiles(float|int $minutesToExpire = 5 * 60): int
    {
        return $this
            ->getExpiredList($minutesToExpire)
            ->each(fn (File $file) => $this->unlink($file))
            ->count();
    }

    /**
     * Returns list of all files uploaded to storage but not assigned to any entity
     *
     * @param float|int $minutesToExpire
     * @return File[]|Builder[]|Collection
     */
    public function getExpiredList(float|int $minutesToExpire = 5 * 60): Collection|array
    {
        $expiredFiles = $this->model->newQuery()->where(function(Builder $query) {
            $query->whereNull('fileable_type');
            $query->orWhereNull('fileable_id');
        })->where('created_at', '<', Carbon::now()->subMinutes($minutesToExpire));

        // query to filter files without user
        return $expiredFiles->get();
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
        $dbFiles = File::query()->pluck('path');

        return array_filter($storage->allFiles($this->storagePath), function($file) use ($dbFiles) {
            return $dbFiles->search(str_start($file, '/')) === false;
        });
    }
}
