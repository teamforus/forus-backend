<?php

namespace App\Http\Resources;

use App\Models\FundAmountPreset;
use Illuminate\Http\Request;

/**
 * @property FundAmountPreset $resource
 */
class FundAmountPresetResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->resource->only([
            'id', 'name', 'amount', 'amount_locale',
        ]);
    }
}
