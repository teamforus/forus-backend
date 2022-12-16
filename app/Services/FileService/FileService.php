<?php

namespace App\Services\FileService;

use App\Services\FileService\Models\File;
use Carbon\Carbon;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
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
     * Remove expired and missing from db files
     */
    public function clear(): void
    {
        $this->clearFilesWithDeletedFileable();
        $this->clearExpiredFiles();
        $this->clearStorage();
    }

    /**
     * Delete all files that were assigned, but objects no longer exists
     *
     * @return int
     */
    public function clearFilesWithDeletedFileable(): int
    {
        return $this->model
            ->with('fileable')
            ->whereNotNull('fileable_id')
            ->whereNotNull('fileable_type')
            ->get()
            ->filter(fn (File $file) => !$file->fileable)
            ->each(fn (File $file) => $this->unlink($file))
            ->count();
    }

    /**
     * Clear file that are created but not assigned to any resource
     *
     * @return int
     */
    public function clearExpiredFiles(): int
    {
        return $this
            ->getExpiredQuery()
            ->get()
            ->each(fn (File $file) => $this->unlink($file))
            ->count();
    }

    /**
     * Clear files that are missing in database from storage
     *
     * @return int count files deleted
     */
    public function clearStorage(): int
    {
        $storage = $this->storage();

        $dbFiles = File::query()->pluck('path')->toArray();
        $storageFiles = $storage->allFiles($this->storagePath);

        return collect($storageFiles)
            ->filter(fn (string $file) => !in_array($file, $dbFiles))
            ->each(fn (string $file) => $storage->delete($file))
            ->count();
    }

    /**
     * Returns list of expired File Models
     *
     * @param string|null $identity_address
     * @return Builder
     */
    public function getExpiredQuery(string $identity_address = null): Builder
    {
        $expiredFiles = $this->model
            ->newQuery()
            ->whereNotNull('fileable_id')
            ->whereNotNull('fileable_type')
            ->where('created_at', '<', Carbon::now()->subMinutes(60));

        if ($identity_address) {
            $expiredFiles->whereIdentityAddress($identity_address);
        }

        return $expiredFiles;
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
     * @return File
     */
    public function uploadSingle(UploadedFile $file, string $type): File
    {
        return $this->doUpload(
            $file->getRealPath(),
            $file->getClientOriginalName(),
            $file->getClientOriginalExtension(),
            $type,
            $file->getSize()
        );
    }

    /**
     * @param string $file_path
     * @param string $original_name
     * @param string $ext
     * @param string $type
     * @param string $size
     * @return File
     */
    protected function doUpload(
        string $file_path,
        string $original_name,
        string $ext,
        string $type,
        string $size
    ): File {
        $uid = File::makeUid();
        $name = $this->makeUniqueFileName($this->storagePath, $ext);

        $path = str_start($name . '.' . $ext, '/');
        $path = str_start($this->storagePath . $path, '/');

        $this->storage()->put($path, file_get_contents($file_path), 'private');

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
}
