<?php

namespace App\Http\Requests\Api\Platform\Organizations\Provider\FundUnsubscribes;

use App\Http\Requests\BaseFormRequest;
use App\Models\FundProviderUnsubscribe;

class UpdateFundUnsubscribeRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{canceled: 'nullable|boolean|required'}
     */
    public function rules(): array
    {
        return [
            'canceled' => 'nullable|boolean|required',
        ];
    }
}
