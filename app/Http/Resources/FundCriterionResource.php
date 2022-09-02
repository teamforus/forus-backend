<?php

namespace App\Http\Resources;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\FundCriterion;
use App\Models\FundCriterionValidator;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property FundCriterion $resource
 */
class FundCriterionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $baseRequest = BaseFormRequest::createFrom($request);
        $criterion = $this->resource;
        $fund = $this->resource->fund;
        $is_valid = $this->isValid($request, $fund, $baseRequest->auth_address());

        $recordTypes = array_pluck(record_types_cached(), 'name', 'key');
        $external_validators = $criterion->fund_criterion_validators;

        return array_merge($criterion->only([
            'id', 'record_type_key', 'operator', 'value', 'show_attachment',
            'title', 'description', 'description_html',
        ]), [
            'external_validators' => $external_validators->map(static function(
                FundCriterionValidator $validator
            ) {
                return [
                    'organization_validator_id' => $validator->organization_validator_id,
                    'organization_id' => $validator->external_validator->validator_organization_id,
                    'accepted' => $validator->accepted,
                ];
            })->toArray(),
            'record_type_name' => $recordTypes[$criterion->record_type_key],
            'is_valid' => $is_valid
        ]);
    }

    /**
     * @param $request
     * @param Fund $fund
     * @param $auth_address
     * @return bool|null
     */
    private function isValid($request, Fund $fund, $auth_address): ?bool {
        $criterion = $this->resource;
        $checkCriteria = $request->get('check_criteria', false);

        if ($checkCriteria && $auth_address) {
            $record = $fund->getTrustedRecordOfType(
                $auth_address,
                $criterion->record_type_key,
                $criterion
            );

            $is_valid = collect($record ? [$record->only('value')] : []);

            return $is_valid->where('value', $criterion->operator, $criterion->value)->count() > 0;
        } else {
            return $checkCriteria ? false : null;
        }
    }
}
