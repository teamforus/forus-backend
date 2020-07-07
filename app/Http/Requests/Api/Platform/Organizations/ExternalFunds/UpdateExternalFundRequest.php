<?php

namespace App\Http\Requests\Api\Platform\Organizations\ExternalFunds;

use App\Models\Fund;
use App\Models\FundCriterion;
use App\Models\Organization;
use App\Scopes\Builders\FundCriteriaQuery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class UpdateExternalFundRequest
 * @property Organization $organization
 * @property Fund $fund
 * @package App\Http\Requests\Api\Platform\Organizations\ExternalFunds
 */
class UpdateExternalFundRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        if ($this->organization && $this->fund) {
            $criteria = FundCriteriaQuery::whereHasExternalValidatorFilter(
                FundCriterion::query(),
                $this->organization->id
            )->where([
                'fund_id' => $this->fund->id
            ])->pluck('fund_criteria.id')->toArray();
        } else {
            $criteria = [];
        }

        return [
            'criteria' => [
                'required',
                'array'
            ],
            'criteria.*.id' => [
                'required',
                Rule::in($criteria)
            ],
            'criteria.*.accepted' => [
                'nullable',
                'boolean'
            ]
        ];
    }
}
