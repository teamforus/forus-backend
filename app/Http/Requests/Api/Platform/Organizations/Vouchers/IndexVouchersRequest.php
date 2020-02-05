<?php

namespace App\Http\Requests\Api\Platform\Organizations\Vouchers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexVouchersRequest extends FormRequest
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
            'fund_id'       => 'nullable|exists:funds,id',
            'granted'       => 'nullable|boolean',
            'amount_min'    => 'nullable|numeric',
            'amount_max'    => 'nullable|numeric',
            'from'          => 'nullable|date_format:Y-m-d',
            'to'            => 'nullable|date_format:Y-m-d',
            'type'          => 'required|in:fund_voucher,product_voucher',
            'unassigned'    => 'nullable|boolean',
            'sort_by'       => [
                'nullable',
                Rule::in([
                    'amount', 'expire_at', 'created_at'
                ])
            ],
            'sort_order'    => [
                'nullable',
                Rule::in([
                    'asc', 'desc'
                ])
            ],
        ];
    }
}
