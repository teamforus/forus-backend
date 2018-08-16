<?php

namespace App\Http\Resources;

use App\Services\MediaService\Models\MediaSize;
use Illuminate\Http\Resources\Json\Resource;

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
        $sizes = collect($this->resource->sizes);

        return collect($this->resource)->only([
            'identity_address', 'original_name', 'type', 'ext', 'uid'
        ])->merge([
            'sizes' => $sizes->keyBy('key')->map(function($size) {
                /** @var MediaSize $size */
                return $size->urlPublic();
            })
        ]);
    }
}
