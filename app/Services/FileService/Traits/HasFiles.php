<?php

namespace App\Services\FileService\Traits;

use App\Services\MediaService\Models\Media;
use Illuminate\Database\Eloquent\Collection;

/**
 * Trait HasMedia
 * @property Collection $medias
 * @package App\Services\MediaService\Traits
 */
trait HasFiles
{
    /**
     * @param Media|null $media
     * @return bool
     */
    public function attachMedia(Media $media = null) {
        if (empty($media)) {
            return false;
        }

        $single = config('media.sizes.' . $media->type . '.type') == 'single';

        if ($single) {
            $this->medias()->where([
                'type' => $media->type
            ])->where('id', '!=', $media->id)->each(function($media) {
                app()->make('media')->unlink($media);
            });
        }

        return $media->update([
            'mediable_type' => static::class,
            'mediable_id' => $this->id,
        ]);
    }

    /**
     * Get all of the mediable's medias.
     *
     * @return mixed
     */
    public function medias()
    {
        return $this->morphMany(Media::class, 'mediable');
    }
}
