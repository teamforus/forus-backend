<?php

namespace App\Http\Resources;

use App\Models\FundCriteriaGroup;
use Illuminate\Http\Request;

/**
 * @property FundCriteriaGroup $resource
 */
class FundCriteriaGroupResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $group = $this->resource;

        return [
            ...$group->only(['id', 'order', 'required']),
            ...$group->translateColumns($group->only([
                'title', 'description_html',
            ])),
        ];
    }
}
