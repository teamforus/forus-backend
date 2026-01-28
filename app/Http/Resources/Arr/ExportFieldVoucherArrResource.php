<?php

namespace App\Http\Resources\Arr;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ExportFieldVoucherArrResource extends ExportFieldArrResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), Arr::only($this->resource, 'is_record_field'));
    }
}
