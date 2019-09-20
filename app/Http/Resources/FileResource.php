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
        $convert_file_size = function ($bytes, $decimals = 2){
            $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
            $factor = floor((strlen($bytes) - 1) / 3);
            return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
        };

        return collect($this->resource)->only([
            'identity_address', 'original_name', 'type', 'ext', 'uid'
        ])->merge([
            'size' => $convert_file_size($this->resource->size),
            // 'url' => $this->resource->urlPublic()
        ]);
    }
}
