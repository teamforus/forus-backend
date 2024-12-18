<?php

namespace App\Http\Resources;

use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Models\MediaPreset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Media $resource
 */
class MediaCompactResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|null
     */
    public function toArray(Request $request): ?array
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
            'sizes' => $presets,
        ]);
    }
}
