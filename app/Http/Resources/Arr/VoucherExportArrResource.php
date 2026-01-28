<?php

namespace App\Http\Resources\Arr;

use App\Http\Resources\BaseJsonResource;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * @property-read array $resource
 */
class VoucherExportArrResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return array_merge(Arr::only($this->resource, ['data', 'name']), [
            'files' => array_map(function ($rawFile) {
                return base64_encode($rawFile);
            }, $this->resource['files'] ?? []),
        ]);
    }
}
