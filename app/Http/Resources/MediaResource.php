<?php

namespace App\Http\Resources;

use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Models\MediaPreset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class MediaResource
 * @property Media $resource
 * @package App\Http\Resources
 */
class MediaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     *
     * @return (\Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection|mixed)[]|null
     *
     * @psalm-return array{sizes: \Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection<array-key, string>,...}|null
     */
    public function toArray($request): ?array
    {
        if (is_null($media = $this->resource)) {
            return null;
        }

        $sizes = $media->presets->filter(static function(MediaPreset $preset) {
            return $preset->key !== 'original';
        })->keyBy('key')->map(static function(MediaPreset $preset) {
            return $preset->urlPublic();
        });

        return array_merge($media->only([
            'identity_address', 'original_name', 'type', 'ext', 'uid', 'dominant_color', 'is_dark',
        ]), compact('sizes'));
    }
}
