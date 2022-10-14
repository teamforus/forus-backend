<?php

namespace App\Http\Resources;

use App\Models\Faq;

/**
 * @property Faq $resource
 */
class FaqResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return $this->resource->only([
            'id', 'title', 'description', 'description_html'
        ]);
    }
}
