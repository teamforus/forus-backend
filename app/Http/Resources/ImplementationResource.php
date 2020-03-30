<?php

namespace App\Http\Resources;

use App\Models\Implementation;
use Illuminate\Http\Resources\Json\JsonResource;

class ImplementationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @property Implementation $resource
     * @return array
     */
    public function toArray($request)
    {
        if (is_null($implementation = $this->resource)) {
            return null;
        }

        return collect($this->resource)->only([
            'id', 'key', 'name', 'url_webshop'
        ])->toArray();
    }
}
