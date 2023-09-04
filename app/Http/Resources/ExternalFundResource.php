<?php

namespace App\Http\Resources;

use App\Models\Fund;
use App\Models\FundCriterion;
use App\Scopes\Builders\FundCriteriaQuery;
use App\Scopes\Builders\FundCriteriaValidatorQuery;
use Illuminate\Http\Resources\Json\JsonResource;

class ExternalFundResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $fund           = $this->resource;
        $organization   = $fund->getAttribute('external_validator');
        $recordTypes    = array_pluck(record_types_cached(), 'name', 'key');

        return [
            'id' => $fund->id,
            'name' => $fund->name,
            'logo' => new MediaResource($fund->logo),
            'organization' => $fund->organization->name,
            'criteria' => FundCriteriaQuery::whereHasExternalValidatorFilter(
                $fund->criteria()->getQuery(),
                $organization->id
            )->get()->map(static function(
                FundCriterion $fundCriterion
            ) use ($organization, $recordTypes) {
                return [
                    'id' => $fundCriterion->id,
                    'name' => $recordTypes[$fundCriterion->record_type_key],
                    'accepted' => FundCriteriaValidatorQuery::whereHasExternalValidatorFilter(
                        $fundCriterion->fund_criterion_validators()->getQuery(),
                        $organization->id
                    )->where('accepted', true)->exists(),
                ];
            }),
        ];
    }
}
