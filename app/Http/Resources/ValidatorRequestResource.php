<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\Resource;

class ValidatorRequestResource extends Resource
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
            'id', 'validator_id', 'record_validation_uid', 'record_validation_uid',
            'identity_address', 'record_id', 'state'
        ])->merge([
            'validator' => new ValidatorResource($this->resource->validator)
        ])->toArray();
    }
}
