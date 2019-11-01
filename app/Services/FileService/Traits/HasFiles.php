<?php

namespace App\Services\FileService\Traits;

use App\Services\FileService\Models\File;
use Illuminate\Database\Eloquent\Collection;

/**
 * Trait HasMedia
 * @property Collection|File[] $files
 * @package App\Services\MediaService\Traits
 */
trait HasFiles
{
    /**
     * @param File|null $file
     * @return bool
     */
    public function attachFile(File $file = null) {
        if (empty($file)) {
            return false;
        }

        return $file->update([
            'fileable_type' => static::class,
            'fileable_id' => $this->id,
        ]);
    }

    /**
     * Get all of the mediable's medias.
     *
     * @return mixed
     */
    public function files()
    {
        return $this->morphMany(File::class, 'fileable');
    }
}
