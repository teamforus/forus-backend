<?php

namespace App\Http\Resources;

use App\Models\FundCriteriaStep;
use Illuminate\Http\Request;

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
    public function toArray(Request $request): array
    {
        $step = $this->resource;

        return [
            'id' => $step->id,
            'order' => $step->order,
            ...$step->translateColumns($step->only([
                'title', 'description_html',
            ]))
        ];
    }
}
