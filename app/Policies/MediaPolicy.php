<?php

namespace App\Policies;

use App\Models\Implementation;
use App\Services\MediaService\Models\Media;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class MediaPolicy
 * @package App\Policies
 */
class MediaPolicy
{
    use HandlesAuthorization;

    /**
     * @param $identity_address
     * @return mixed
     */
    public function viewAny($identity_address): bool
    {
        return !empty($identity_address);
    }

    /**
     * @param $identity_address
     * @return mixed
     */
    public function show($identity_address): bool
    {
        return !empty($identity_address);
    }

    /**
     * @param $identity_address
     * @return mixed
     */
    public function store($identity_address): bool
    {
        return !empty($identity_address);
    }

    /**
     * @param $identity_address
     * @param Media $media
     * @return bool
     */
    public function destroy($identity_address, Media $media): bool
    {
        if ($media->mediable && $media->type == 'implementation_banner') {
            /** @var Implementation $implementation */
            $implementation = $media->mediable;

            return $implementation->organization->identityCan($identity_address, [
                'manage_implementation_cms'
            ]);
        }

        return strcmp($media->identity_address, $identity_address) == 0;
    }
}