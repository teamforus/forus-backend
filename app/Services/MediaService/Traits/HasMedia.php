<?php

namespace App\Services\MediaService\Traits;

use App\Services\MediaService\MediaService;
use App\Services\MediaService\Models\Media;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Trait HasMedia
 * @property Collection $medias
 * @package App\Services\MediaService\Traits
 */
trait HasMedia
{
    /**
     * @param Media $media
     * @return $this
     */
    public function attachMedia(Media $media): self
    {
        $this->syncMedia($media->uid, $media->type);

        return $this;
    }

    /**
     * @param string|null $uid
     * @return $this
     * @noinspection PhpUnused
     */
    public function attachMediaByUid(?string $uid): self
    {
        if ($uid && $media = resolve('media')->findByUid($uid)) {
            return $this->attachMedia($media);
        }

        return $this;
    }

    /**
     * @param string|null $uid
     * @param string|null $cloneType
     * @return $this
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @noinspection PhpUnused
     */
    public function attachOrCloneMediaByUid(?string $uid, string $cloneType): self
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
     * @param string|array $uid
     * @param string $mediaConfigType
     * @return bool
     */
    public function syncMedia($uid, string $mediaConfigType): bool
    {
        $uid = (array) $uid;
        $mediaConfig = MediaService::getMediaConfig($mediaConfigType);
        $multiple = $mediaConfig && ($mediaConfig->getType() === $mediaConfig::TYPE_MULTIPLE);

        if (!$mediaConfig || (!$multiple && count($uid) > 1)) {
            return false;
        }

        // remove old medias
        $oldMedia = $this->medias()->where([
            'type' => $mediaConfig->getName()
        ])->whereNotIn('uid', $uid)->pluck('uid')->toArray();

        $this->unlinkMedias($oldMedia);

        // attach new medias
        /** @var Media[] $newMedia */
        $newMedia = Media::whereType($mediaConfig->getName())->whereIn('uid', $uid)->get();
        $order = array_flip(array_diff($uid, $oldMedia));

        foreach ($newMedia as $media) {
            $media->update([
                'order'         => $order[$media->uid],
                'mediable_type' => $this->getMorphClass(),
                'mediable_id'   => $this->id,
            ]);
        }

        return true;
    }

    /**
     * @param string|array $uid
     * @param string $mediaConfigType
     * @return bool
     */
    public function appendMedia($uid, string $mediaConfigType): bool
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
    public function unlinkMedias($uid) {
        $media = $this->medias()->whereIn('uid', (array) $uid)->get();

        try {
            $media->each(function(Media $media) {
                resolve('media')->unlink($media);
            });
        } catch (\Exception $exception) {
            if ($logger = logger()) {
                $logger->error($exception->getMessage());
            }
        }
    }

    /**
     * Get all of the mediable's medias.
     * @return MorphMany
     */
    public function medias(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }
}