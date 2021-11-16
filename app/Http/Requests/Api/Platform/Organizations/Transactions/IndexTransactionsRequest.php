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
            'from'          => 'date_format:Y-m-d',
            'to'            => 'date_format:Y-m-d',
            'amount_min'    => 'numeric|min:0',
            'amount_max'    => 'numeric|min:0',
            'export_format' => 'nullable|in:csv,xls',

            'fund_ids'          => 'nullable|array',
            'fund_ids.*'        => 'required|exists:funds,id',
            'postcodes'         => 'nullable|array',
            'postcodes.*'       => 'nullable|string|max:100',
            'provider_ids'      => 'nullable|array',
            'provider_ids.*'    => 'nullable|exists:organizations,id',
            'pending_bulking'   => 'nullable|boolean',

            'product_category_ids'          => 'nullable|array',
            'product_category_ids.*'        => 'nullable|exists:product_categories,id',
            'voucher_transaction_bulk_id'   => 'nullable|exists:voucher_transaction_bulks,id',
        ];
    }
}
