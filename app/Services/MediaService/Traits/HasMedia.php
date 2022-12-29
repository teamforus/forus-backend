<?php

namespace App\Services\MediaService\Traits;

use App\Services\MediaService\MediaService;
use App\Services\MediaService\Models\Media;
use Barryvdh\LaravelIdeHelper\Eloquent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property Collection $medias
 * @extends Eloquent
 */
trait HasMedia
{
    /**
     * @param Media $media
     * @return static
     */
    public function attachMedia(Media $media): static
    {
        $this->syncMedia($media->uid, $media->type);

        return $this;
    }

    /**
     * @param string|null $uid
     * @return static
     * @noinspection PhpUnused
     */
    public function attachMediaByUid(?string $uid): static
    {
        if ($uid && $media = resolve('media')->findByUid($uid)) {
            return $this->attachMedia($media);
        }

        return $this;
    }

    /**
     * @param string|null $uid
     * @param string $cloneType
     * @return static
     * @throws \Throwable
     * @noinspection PhpUnused
     */
    public function attachOrCloneMediaByUid(?string $uid, string $cloneType): static
    {
        if ($uid && $media = resolve('media')->findByUid($uid)) {
            // media already assigned to other entity or the type is wrong
            if (($media->mediable && !$media->mediable->is($this)) || $media->type != $cloneType) {
                $media = resolve('media')->cloneMedia($media, $cloneType, true);
            }

            return $this->attachMedia($media);
        }

        return $this;
    }

    /**
     * @param array|string $uid
     * @param string $mediaConfigType
     * @return bool
     */
    public function syncMedia(array|string $uid, string $mediaConfigType): bool
    {
        $uid = (array) $uid;
        $mediaConfig = MediaService::getMediaConfig($mediaConfigType);
        $multiple = $mediaConfig && ($mediaConfig->getType() === $mediaConfig::TYPE_MULTIPLE);

        if (!$mediaConfig || (!$multiple && count($uid) > 1)) {
            return false;
        }

        // remove old medias
        $oldMedia = $this->medias()->where([
            'type' => $mediaConfig->getName(),
        ])->whereNotIn('uid', $uid)->pluck('uid')->toArray();

        $this->unlinkMedias($oldMedia);

        // attach new medias
        $newMedia = Media::whereType($mediaConfig->getName())->whereIn('uid', $uid)->get();
        $order = array_flip(array_diff($uid, $oldMedia));

        $newMedia->each(fn (Media $media) => $media->update([
            'order'         => $order[$media->uid],
            'mediable_type' => $this->getMorphClass(),
            'mediable_id'   => $this->id,
        ]));

        return true;
    }

    /**
     * @param array|string $uid
     * @param string $mediaConfigType
     * @return bool
     */
    public function appendMedia(array|string $uid, string $mediaConfigType): bool
    {
        $uid = $this->medias()->where([
            'type' => $mediaConfigType,
        ])->pluck('uid')->merge((array) $uid)->toArray();

        return $this->syncMedia($uid, $mediaConfigType);
    }

    /**
     * Remove medias by uid
     * @param array|string $uid
     */
    public function unlinkMedias(array|string $uid): void
    {
        $media = $this->medias()->whereIn('uid', (array) $uid)->get();

        try {
            $media->each(fn(Media $media) => resolve('media')->unlink($media));
        } catch (\Throwable $e) {
            logger()?->error($e->getMessage());
        }
    }

    /**
     * Get all the mediable medias.
     * @return MorphMany
     */
    public function medias(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }
}