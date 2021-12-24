<?php

namespace App\Http\Resources;

use App\Models\FundCategory;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class FundCriterionResource
 * @property FundCategory $resource
 * @package App\Http\Resources
 */
class FundCategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $fundCategory = $this->resource;

        return array_merge($fundCategory->only('id', 'fund_id', 'tag_id'), [
            'tag_name' => $fundCategory->tag->name,
            'tag_key'  => $fundCategory->tag->key,
        ]);
    }
}
