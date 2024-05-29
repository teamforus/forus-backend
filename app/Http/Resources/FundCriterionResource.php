<?php

namespace App\Http\Resources;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\FundCriterion;
use App\Models\Identity;
use Illuminate\Http\Request;

/**
 * @property FundCriterion $resource
 */
class FundCriterionResource extends BaseJsonResource
{
    const LOAD = [
        'fund_criterion_rules',
        'record_type.translation',
        'record_type.record_type_options',
        'fund_criterion_validators.external_validator',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $identity =  BaseFormRequest::createFrom($request)->identity();
        $criterion = $this->resource;

        return array_merge($criterion->only([
            'id', 'record_type_key', 'operator', 'show_attachment', 'order',
            'title', 'description', 'description_html', 'record_type',
            'min', 'max', 'optional', 'value', 'fund_criteria_step_id',
        ]), [
            'external_validators' => $criterion->fund_criterion_validators->map(fn ($validator) => [
                'organization_validator_id' => $validator->organization_validator_id,
                'organization_id' => $validator->external_validator->validator_organization_id,
                'accepted' => $validator->accepted,
            ])->toArray(),
            'rules' => $criterion->fund_criterion_rules->map(fn ($criterion) => $criterion->only([
                'record_type_key', 'operator', 'value',
            ]))->toArray(),
            'record_type' => [
                ...$criterion->record_type->only([
                    'name', 'key', 'type'
                ]),
                'options' => $criterion->record_type->getOptions(),
            ],
            'is_valid' => $this->isValid($request, $criterion->fund, $identity),
            'has_record' => $this->hasTrustedRecord($request, $criterion->fund, $identity),
        ]);
    }

    /**
     * @param Request $request
     * @param Fund $fund
     * @param Identity|null $identity
     * @return bool|null
     */
    private function isValid(Request $request, Fund $fund, ?Identity $identity): ?bool
    {
        $checkCriteria = $request->get('check_criteria', false);

        if ($checkCriteria && $identity) {
            return $fund->checkFundCriteria($identity, $this->resource);
        }

        return $checkCriteria ? false : null;
    }

    /**
     * @param Request $request
     * @param Fund $fund
     * @param Identity|null $identity
     * @return bool|null
     */
    private function hasTrustedRecord(Request $request, Fund $fund, ?Identity $identity): ?bool
    {
        $checkCriteria = $request->get('check_criteria', false);

        if ($checkCriteria && $identity) {
            return !empty($fund->getTrustedRecordOfType(
                $identity->address,
                $this->resource->record_type_key,
                $this->resource,
            ));
        }

        return $checkCriteria ? false : null;
    }
}
