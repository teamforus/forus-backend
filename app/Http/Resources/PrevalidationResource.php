<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\Resource;

class PrevalidationResource extends Resource
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
            'id', 'uid', 'state'
        ])->merge([
            'records' => PrevalidationRecordResource::collection(
                $this->resource->records
            )
        ])->toArray();
    }
}
