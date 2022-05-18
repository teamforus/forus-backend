<?php

namespace App\Http\Resources;

use App\Models\DemoTransaction;

/**
 * @property DemoTransaction $resource
 */
class DemoTransactionResource extends BaseJsonResource
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
            'id', 'token', 'state',
        ]), $this->timestamps($this->resource, 'created_at', 'updated_at'));
    }
}
