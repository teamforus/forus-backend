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
     * @param  \Illuminate\Http\Request|any  $request
     * @return array
     */
    public function toArray($request): ?array
    {
        $media = $this->resource;

        if (!$media) {
            return null;
        }

        $presets = $media->presets->filter(static function(MediaPreset $preset) {
            return $preset->key !== 'original';
        })->keyBy('key')->map(static function(MediaPreset $preset) {
            return $preset->urlPublic();
        });

        return array_merge($media->only([
            'original_name', 'type', 'ext', 'uid', 'dominant_color'
        ]), [
            'dominant_color' => $media->dominant_color ?? null,
            'sizes' => $presets
        ]);
    }
}
