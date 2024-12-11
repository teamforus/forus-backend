<?php

namespace App\Http\Requests\Api\Platform\Funds;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Traits\ThrottleWithMeta;

/**
 * @property Fund $fund
 */
class ViewFundRequest extends BaseFormRequest
{
    use ThrottleWithMeta;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return
            in_array($this->fund->state, Fund::STATES_PUBLIC, true) && (
                $this->implementation()?->isGeneral() ||
                $this->implementation()?->id === $this->fund?->fund_config?->implementation_id
            );
    }
}
