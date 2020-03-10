<?php

namespace App\Http\Resources;

use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Models\MediaPreset;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class MediaResource
 * @property Media $resource
 * @package App\Http\Resources
 */
class MediaResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        if (is_null($media = $this->resource)) {
            return null;
        }

        $presets = $media->presets->filter(function(MediaPreset $preset) {
            return $preset->key != 'original';
        })->keyBy('key')->map(function(MediaPreset $preset) {
            return $preset->urlPublic();
        });

        return collect($media)->only([
            'identity_address', 'original_name', 'type', 'ext', 'uid',
        ])->merge([
            'dominant_color' => $media->dominant_color ?? null,
            'sizes' => $presets
        ])->toArray();
    }
}
