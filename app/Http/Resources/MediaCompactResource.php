<?php

namespace App\Http\Resources;

use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Models\MediaPreset;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class MediaCompactResource
 * @property Media $resource
 * @package App\Http\Resources
 */
class MediaCompactResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $presets = collect($this->resource->presets);

        return collect($this->resource)->only([
            'original_name', 'type', 'ext', 'uid', 'dominant_color'
        ])->merge([
            'sizes' => $presets->keyBy('key')->map(function(MediaPreset $preset) {
                return $preset->urlPublic();
            })
        ])->toArray();
    }
}
