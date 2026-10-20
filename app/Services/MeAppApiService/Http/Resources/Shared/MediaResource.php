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
     * @return array{sizes: array{thumbnail: string|null, large: string|null}}|null
     */
    public function toArray(Request $request): ?array
    {
        if (!$media = $this->resource) {
            return null;
        }

        $presets = $media->presets->keyBy('key');
        /** @var MediaPreset|null $thumbnail */
        $thumbnail = $presets->get('thumbnail');
        /** @var MediaPreset|null $large */
        $large = $presets->get('large');

        return [
            'sizes' => [
                'thumbnail' => $thumbnail?->urlPublic(),
                'large' => $large?->urlPublic(),
            ],
        ];
    }
}
