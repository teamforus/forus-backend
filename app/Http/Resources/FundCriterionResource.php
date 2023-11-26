<?php

namespace App\Http\Resources;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\FundCriterion;
use App\Models\FundCriterionValidator;
use App\Models\Identity;

/**
 * @property FundCriterion $resource
 */
class FundCriterionResource extends BaseJsonResource
{
    const LOAD = [
        'record_type.translation',
        'fund_criterion_validators.external_validator',
    ];

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
        $external_validators = $criterion->fund_criterion_validators;

        return array_merge($criterion->only([
            'id', 'record_type_key', 'operator', 'show_attachment',
            'title', 'description', 'description_html', 'record_type',
            'min', 'max', 'optional'
        ]), [
            'value' => $this->getValue($criterion),
            'external_validators' => $external_validators->map(static function(
                FundCriterionValidator $validator
            ) {
                return [
                    'organization_validator_id' => $validator->organization_validator_id,
                    'organization_id' => $validator->external_validator->validator_organization_id,
                    'accepted' => $validator->accepted,
                ];
            })->toArray(),
            'record_type' => [
                ...$criterion->record_type->only(['name', 'key', 'type']),
                'options' => $criterion->record_type->getOptions(),
            ],
            'is_valid' => $this->isValid($request, $fund, $baseRequest->identity()),
            'has_record' => $this->hasTrustedRecord($fund, $baseRequest->identity()),
        ]);
    }

    /**
     * @param FundCriterion $criterion
     * @return string
     */
    protected function getValue(FundCriterion $criterion): string
    {
        if ($criterion->record_type?->type == 'bool') {
            return $criterion->value ? 'Ja' : 'Nee';
        }

        return $criterion->value;
    }

    /**
     * @param $request
     * @param Fund $fund
     * @param Identity|null $identity
     * @return bool|null
     */
    private function isValid($request, Fund $fund, ?Identity $identity): ?bool
    {
        $checkCriteria = $request->get('check_criteria', false);

        if ($checkCriteria && $identity) {
            return $fund->checkFundCriteria($identity, $this->resource);
        }

        return $checkCriteria ? false : null;
    }

    /**
     * @param Fund $fund
     * @param Identity|null $identity
     * @return bool|null
     */
    private function hasTrustedRecord(Fund $fund, ?Identity $identity): ?bool
    {
        return $identity && !empty($fund->getTrustedRecordOfType(
            $identity->address,
            $this->resource->record_type_key,
            $this->resource,
        ));
    }
}
