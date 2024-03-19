<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds;

use App\Models\Fund;

/**
 * @property null|Fund $fund
 */
class UpdateFundCriteriaRequest extends BaseFundRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            ...$this->criteriaRule($this->fund->criteria()->pluck('id')->toArray()),
        ];
    }
}
