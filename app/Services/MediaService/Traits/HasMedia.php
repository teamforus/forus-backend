<?php

namespace App\Services\MediaService\Traits;



use App\Services\MediaService\Models\Media;
use Illuminate\Database\Eloquent\Collection;

/**
 * Trait HasMedia
 * @property Collection $medias
 * @package App\Services\MediaService\Traits
 */
trait HasMedia
{
    /**
     * @param Media $media
     */
    public function attachMedia(Media $media) {
        if (config('media.sizes.' . $media->type . '.type') == 'single') {
            $this->medias->each(function($media) {
                app()->make('media')->unlink($media);
            });
        }

        $media->update([
            'mediable_type' => static::class,
            'mediable_id' => $this->id,
        ]);
    }

    /**
     * Get all of the mediable's medias.
     */
    public function medias()
    {
        return $this->morphMany(Media::class, 'mediable');
    }
}