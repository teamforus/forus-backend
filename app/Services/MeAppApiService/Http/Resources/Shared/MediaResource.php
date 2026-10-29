<?php

namespace App\Services\MeAppApiService\Http\Resources\Shared;

use App\Http\Resources\BaseJsonResource;
use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Models\MediaPreset;
use Illuminate\Http\Request;

/**
 * @property-read Media $resource
 */
class MediaResource extends BaseJsonResource
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

        $presets = $media->presets->keyBy('key');
        /** @var MediaPreset|null $thumbnail */
        $thumbnail = $presets->get('thumbnail');
        /** @var MediaPreset|null $small */
        $small = $presets->get('small');
        /** @var MediaPreset|null $large */
        $large = $presets->get('large');

        return [
            'sizes' => [
                'thumbnail' => $thumbnail?->urlPublic(),
                'small' => $small?->urlPublic(),
                'large' => $large?->urlPublic(),
            ],
        ];
    }
}
