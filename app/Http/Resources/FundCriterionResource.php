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
    const array LOAD = [
        'fund_criterion_rules',
        'record_type.translation',
        'record_type.record_type_options',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $identity =  BaseFormRequest::createFrom($request)->identity();
        $criterion = $this->resource;

        return array_merge($criterion->only([
            'id', 'record_type_key', 'operator', 'show_attachment', 'order',
            'title', 'description', 'description_html', 'record_type', 'label',
            'min', 'max', 'optional', 'value', 'fund_criteria_step_id',
            'extra_description', 'extra_description_html',
        ]), [
            'rules' => $criterion->fund_criterion_rules->map(fn ($criterion) => $criterion->only([
                'record_type_key', 'operator', 'value',
            ]))->toArray(),
            'record_type' => [
                ...$criterion->record_type->only([
                    'name', 'key', 'type', 'control_type',
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
            return !empty($fund->getTrustedRecordOfType($identity, $this->resource->record_type_key));
        }

        return $checkCriteria ? false : null;
    }
}
