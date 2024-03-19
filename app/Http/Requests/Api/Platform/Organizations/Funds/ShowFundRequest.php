<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds;

use App\Http\Requests\BaseFormRequest;

class ShowFundRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{stats: 'nullable|string|in:all,budget,product_vouchers'}
     */
    public function rules(): array
    {
        return [
            'stats' => 'nullable|string|in:all,budget,product_vouchers',
        ];
    }
}
