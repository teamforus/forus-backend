<?php

namespace App\Http\Requests\Api\Platform\Organizations\Transactions;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\VoucherTransaction;
use Illuminate\Validation\Rule;

abstract class BaseIndexTransactionsRequest extends BaseFormRequest
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
            'per_page'      => $this->perPageRule(),
            'q'             => 'nullable|string',
            'state'         => ['nullable', Rule::in(VoucherTransaction::STATES)],
            'fund_state'    => ['nullable', Rule::in(Fund::STATES)],
            'from'          => 'nullable|date_format:Y-m-d',
            'to'            => 'nullable|date_format:Y-m-d',
            'amount_min'    => 'nullable|numeric|min:0',
            'amount_max'    => 'nullable|numeric|min:0',
            'data_format'   => 'nullable|in:csv,xls',

            'fund_id'           => 'nullable|exists:funds,id',
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

            'order_by'          => 'nullable|in:' . implode(',', VoucherTransaction::SORT_BY_FIELDS),
            'order_dir'         => 'nullable|in:asc,desc',
        ];
    }
}
