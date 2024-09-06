<?php

namespace App\Http\Requests\Api\Platform\Organizations\Transactions;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\Organization;
use App\Models\VoucherTransaction;
use App\Models\ProductReservation;
use App\Models\VoucherTransactionBulk;
use Illuminate\Validation\Rule;

/**
 * @property Organization $organization
 */
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
        $fundIds = $this->organization?->funds?->pluck('id')->toArray();

        return [
            'per_page' => $this->perPageRule(),
            'q' => 'nullable|string',
            'state' => ['nullable', Rule::in(VoucherTransaction::STATES)],
            'fund_state' => ['nullable', Rule::in(Fund::STATES)],
            'from' => 'nullable|date_format:Y-m-d',
            'to' => 'nullable|date_format:Y-m-d',
            'amount_min' => 'nullable|numeric|min:0',
            'amount_max' => 'nullable|numeric|min:0',
            'data_format' => 'nullable|in:csv,xls',

            'transfer_in_min' => 'nullable|numeric|min:0',
            'transfer_in_max' => 'nullable|numeric|max:' . ProductReservation::TRANSACTION_DELAY,

            'fund_id' => 'nullable|exists:funds,id',
            'fund_ids' => 'nullable|array',
            'fund_ids.*' => 'required|exists:funds,id',
            'postcodes' => 'nullable|array',
            'postcodes.*' => 'nullable|string|max:100',
            'provider_ids' => 'nullable|array',
            'provider_ids.*' => 'nullable|exists:organizations,id',
            'pending_bulking' => 'nullable|boolean',

            'non_cancelable_from' => 'nullable|date_format:Y-m-d',
            'non_cancelable_to' => 'nullable|date_format:Y-m-d',
            'bulk_state' => ['nullable', Rule::in(VoucherTransactionBulk::STATES)],

            'voucher_id' => [
                'nullable',
                Rule::exists('vouchers', 'id')->whereIn('fund_id', $fundIds),
            ],
            'reservation_voucher_id' => [
                'nullable',
                Rule::exists('vouchers', 'id')->whereIn('fund_id', $fundIds),
            ],

            'product_category_ids' => 'nullable|array',
            'product_category_ids.*' => 'nullable|exists:product_categories,id',
            'voucher_transaction_bulk_id' => 'nullable|exists:voucher_transaction_bulks,id',

            'order_by' => ['nullable', Rule::in(VoucherTransaction::SORT_BY_FIELDS)],
            'order_dir' => 'nullable|in:asc,desc',
            'targets' => 'nullable|array',
            'targets.*' => ['required', Rule::in(VoucherTransaction::TARGETS)],
            'initiator' => ['nullable', Rule::in(VoucherTransaction::INITIATORS)],
        ];
    }
}
