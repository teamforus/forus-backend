<?php

namespace App\Http\Requests\Api\Platform\Funds;

use App\Http\Requests\BaseFormRequest;

class RedeemFundsRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return (bool) $this->auth_address();
    }
}
