<?php

namespace App\Http\Resources\Small;

use App\Http\Resources\BaseJsonResource;
use App\Services\MediaService\Models\Media;
use Illuminate\Http\Request;

/**
 * @mixin Media
 * @property-read Media|null $resource
 */
class MediaSmallResource extends BaseJsonResource
{
    public const array LOAD = [
        'presets',
    ];

    /**
     * @param Request $request
     * @return array|null
     */
    public function toArray(Request $request): ?array
    {
        if (!$media = $this->resource) {
            return null;
        }

        return [
            'sizes' => [
                'thumbnail' => $media->urlPublic('thumbnail'),
                'small' => $media->urlPublic('small'),
                'large' => $media->urlPublic('large'),
            ],
        ];
    }
}
