<?php

namespace App\Services\FileService\Traits;

use App\Services\FileService\Models\File;
use Barryvdh\LaravelIdeHelper\Eloquent;
use Illuminate\Database\Eloquent\Collection;

/**
 * @property Collection|File[] $files
 * @extends Eloquent
 */
trait HasFiles
{
    /**
     * Append given files to the model
     *
     * @param string|array|null $uid
     * @return static
     */
    public function appendFilesByUid(string|array $uid = null): static
    {
        $uid = (array) $uid;

        $filesQuery = File::query()
            ->whereIn('uid', $uid)
            ->whereNull('fileable_id')
            ->whereNull('fileable_type');

        foreach ($filesQuery->get() as $file) {
            $file->update([
                'order'         => array_search($file->uid, $uid),
                'fileable_id'   => $this->id,
                'fileable_type' => $this->getMorphClass(),
            ]);
        }

        return $this;
    }

    /**
     * Append given files to the model and remove the files that are not in the given list
     *
     * @param string|array|null $uid
     * @return static
     */
    public function syncFilesByUid(string|array $uid = null): static
    {
        $uid = (array) $uid;

        $this->files()->whereNotIn('uid', $uid)->each(fn (File $file) => $file->unlink());

        return $this->appendFilesByUid($uid);
    }

    /**
     * Get all the fileable files.
     *
     * @return mixed
     */
    public function files(): mixed
    {
        return $this
            ->morphMany(File::class, 'fileable')
            ->orderBy('order');
    }
}
