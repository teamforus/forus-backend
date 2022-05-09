<?php
namespace App\Http\Resources;

use App\Services\FileService\Models\File;

/**
 * Class FileResource
 * @property File $resource
 * @package App\Http\Resources
 */
class FileResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return array_merge($this->resource->only([
            'identity_address', 'original_name', 'type', 'ext', 'uid',
        ]), [
            'size' => pretty_file_size($this->resource->size),
            'url'  => $this->resource->urlPublic()
        ]);
    }
}
