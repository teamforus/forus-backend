<?php

namespace Tests\Traits;

use App\Models\Identity;
use App\Services\MediaService\Models\Media;
use Exception;
use Illuminate\Http\UploadedFile;

trait MakesCmsMedia
{
    /**
     * @param string $mediaType
     * @param Identity $identity
     * @throws Exception
     * @return Media
     */
    protected function makeMedia(string $mediaType, Identity $identity): Media
    {
        $fileName = 'media.jpg';
        $file = UploadedFile::fake()->image($fileName);

        return tap(
            resolve('media')->uploadSingle($file, $fileName, $mediaType),
            function (Media $media) use ($identity) {
                $media->forceFill(['identity_address' => $identity->address])->save();
            },
        );
    }
}
