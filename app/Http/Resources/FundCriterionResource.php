<?php

namespace App\Http\Resources;

use App\Models\FundCriterion;
use App\Models\FundCriterionValidator;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class FundCriterionResource
 * @property FundCriterion $resource
 * @package App\Http\Resources
 */
class FundCriterionResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request|any  $request
     * @return array
     */
    public function toArray($request): array
    {
        $criterion = $this->resource;
        $fund = $this->resource->fund;

        $recordTypes = array_pluck(record_types_cached(), 'name', 'key');
        $external_validators = $this->resource->fund_criterion_validators;
        $checkCriteria = $request->get('check_criteria', false);

        if ($checkCriteria && auth_address()) {
            $record = $fund::getTrustedRecordOfType(
                auth_address(),
                $criterion->record_type_key,
                $criterion->fund,
                $criterion
            );
            $is_valid = collect($record ? [$record] : [])->where(
                'value', $criterion->operator, $criterion->value
                )->count() > 0;
        } else {
            $is_valid = $checkCriteria ? false : null;
        }

        return array_merge($this->resource->only([
            'id', 'record_type_key', 'operator', 'value', 'show_attachment',
            'description', 'title'
        ]), [
            'description_html' => resolve('markdown')->convertToHtml(
                $this->resource->description
            ),
            'external_validators' => $external_validators->map(static function(
                FundCriterionValidator $validator
            ) {
                return [
                    'organization_validator_id' => $validator->organization_validator_id,
                    'organization_id' => $validator->external_validator->validator_organization_id,
                    'accepted' => $validator->accepted,
                ];
            })->toArray(),
            'record_type_name' => $recordTypes[$this->resource->record_type_key],
            'show_attachment' => $this->resource->show_attachment ? true : false,
            'is_valid' => $is_valid
        ]);
    }
}
