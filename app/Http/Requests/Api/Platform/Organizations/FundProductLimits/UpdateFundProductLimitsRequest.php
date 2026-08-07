<?php

namespace App\Http\Requests\Api\Platform\Organizations\FundProductLimits;

use App\Models\FundProductLimit;
use App\Models\Organization;
use Illuminate\Support\Facades\Gate;

/**
 * @property-read Organization $organization
 * @property-read FundProductLimit $fund_product_limit
 */
class UpdateFundProductLimitsRequest extends StoreFundProductLimitsRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('update', [$this->fund_product_limit, $this->organization]);
    }
}
