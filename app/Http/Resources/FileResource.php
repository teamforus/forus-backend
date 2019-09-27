<?php
namespace App\Http\Resources;

use App\Services\FileService\Models\File;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class FileResource
 * @property File $resource
 * @package App\Http\Resources
 */
class FileResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return collect($this->resource)->only([
            'identity_address', 'original_name', 'type', 'ext', 'uid'
        ])->merge([
            'size' => pretty_file_size($this->resource->size)
        ]);
    }
}
