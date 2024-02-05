<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds;

use App\Models\Fund;

/**
 * @property null|Fund $fund
 */
class UpdateFundCriteriaRequest extends BaseFundRequest
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
        return [
            ...$this->criteriaRule($this->fund->criteria()->pluck('id')->toArray()),
        ];
    }
}
