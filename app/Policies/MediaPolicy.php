<?php

namespace App\Policies;

use App\Models\Identity;
use App\Models\Implementation;
use App\Services\MediaService\Models\Media;
use Illuminate\Auth\Access\HandlesAuthorization;

class MediaPolicy
{
    use HandlesAuthorization;

    /**
     * @param Identity $identity
     * @return mixed
     */
    public function viewAny(Identity $identity): bool
    {
        return $identity->exists;
    }

    /**
     * @param Identity $identity
     * @param Media $media
     * @return bool
     */
    public function show(Identity $identity, Media $media): bool
    {
        return $identity->address == $media->identity_address;
    }

    /**
     * @param Identity $identity
     * @return bool
     */
    public function store(Identity $identity): bool
    {
        return $identity->exists;
    }

    /**
     * @param Identity $identity
     * @param Media $media
     * @return bool
     */
    public function destroy(Identity $identity, Media $media): bool
    {
        if ($media->mediable && in_array($media->type, ['implementation_banner', 'email_logo'])) {
            $implementation = $media->mediable instanceof Implementation ? $media->mediable : null;

            return $implementation?->organization->identityCan($identity, [
                'manage_implementation_cms',
            ]);
        }

        return $identity->address == $media->identity_address;
    }
}