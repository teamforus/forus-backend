<?php

namespace App\Http\Resources\Arr;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

/**
 * @property-read array $resource
 */
class VoucherExportArrResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return array_merge(Arr::only($this->resource, ['data', 'name']), [
            'files' => array_map(function($rawFile) {
                return base64_encode($rawFile);
            }, $this->resource['files'] ?? []),
        ]);
    }
}
