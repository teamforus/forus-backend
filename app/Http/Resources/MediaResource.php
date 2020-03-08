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
        if (is_null($this->resource)) {
            return null;
        }

        $presets = collect($this->resource->presets);

        return collect($this->resource)->only([
            'identity_address', 'original_name', 'type', 'ext', 'uid', 'dominant_color'
        ])->merge([
            'sizes' => $presets->keyBy('key')->map(function(MediaPreset $preset) {
                return $preset->urlPublic();
            })
        ])->toArray();
    }
}
