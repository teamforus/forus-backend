<?php

namespace App\Http\Resources;

use App\Models\FundCriteriaStep;

/**
 * @property FundCriteriaStep $resource
 */
class FundCriteriaStepResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return $this->resource->only([
            'id', 'title', 'order',
        ]);
    }
}
