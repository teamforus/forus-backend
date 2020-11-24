<?php

namespace App\Http\Requests\Api\Platform\Organizations\Transactions;

use App\Models\Fund;
use App\Models\VoucherTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexTransactionsRequest extends FormRequest
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
            'per_page'      => 'numeric|between:1,100',
            'q'             => 'nullable|string',
            'state'         => Rule::in(VoucherTransaction::STATES),
            'fund_state'    => Rule::in(Fund::STATES),
            'from'          => 'date:Y-m-d',
            'to'            => 'date:Y-m-d',
            'amount_min'    => 'numeric|min:0',
            'amount_max'    => 'numeric|min:0',
        ];
    }
}
