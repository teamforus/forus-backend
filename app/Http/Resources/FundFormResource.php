<?php

namespace App\Http\Resources;

use App\Models\FundForm;
use Illuminate\Http\Request;

/**
 * @property FundForm $resource
 */
class FundFormResource extends BaseJsonResource
{
    /**
     * @param string|null $append
     * @return array
     */
    public static function load(?string $append = null): array
    {
        return [...parent::load($append), ...FundResource::load('fund')];
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $fund = $this->resource->fund;
        $isActive = $fund->isActive() && $fund?->criteria;
        $steps = $fund->criteria_steps->count() + $fund->criteria->whereNull('fund_criteria_step_id')->count();

        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'fund' => new FundResource($fund),
            'state' => $isActive ? 'active' : 'archived',
            'state_locale' => $isActive ? 'Actief' : 'Gearchiveerd',
            'steps' => $steps,
            ...$this->makeTimestamps($this->resource->only([
                'created_at', 'updated_at',
            ])),
        ];
    }
}
