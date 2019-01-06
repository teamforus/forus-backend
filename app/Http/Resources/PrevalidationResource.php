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
        $creatorFields = [];

        if (auth()->id() == $this->resource->identity_address) {
            $creatorFields = collect($this->resource)->only([
                'fund_id', 'created_at', 'identity_address'
            ]);
        }

        return collect($this->resource)->only([
            'id', 'uid', 'state', 'exported'
        ])->merge($creatorFields)->merge([
            'records' => PrevalidationRecordResource::collection(
                $this->resource->records
            )
        ]);
    }
}
