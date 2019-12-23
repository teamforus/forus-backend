<?php

namespace App\Http\Requests\Api\Platform\Organizations\Transactions;

use App\Models\Fund;
use Illuminate\Foundation\Http\FormRequest;

class IndexTransactionsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'per_page'      => 'numeric|between:1,100',
            'q'             => 'nullable|string',
            'state'         => 'nullable|in:pending,success',
            'fund_state'    => 'nullable|in:' . join(',', Fund::STATES),
            'from'          => 'date:Y-m-d',
            'to'            => 'date:Y-m-d',
            'amount_min'    => 'numeric|min:0',
            'amount_max'    => 'numeric|min:0',
        ];
    }
}
