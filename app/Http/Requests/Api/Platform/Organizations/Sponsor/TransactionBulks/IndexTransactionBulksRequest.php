<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\TransactionBulks;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\VoucherTransactionBulk;
use Illuminate\Validation\Rule;

/**
 * @property-read Organization $organization
 */
class IndexTransactionBulksRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->gateAllows([
            'show'      => $this->organization,
            'viewAny'   => [VoucherTransactionBulk::class, $this->organization],
        ]);
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
            'state'         => Rule::in(VoucherTransactionBulk::STATES),
            'from'          => 'date_format:Y-m-d',
            'to'            => 'date_format:Y-m-d',
            'amount_min'    => 'numeric|min:0',
            'amount_max'    => 'numeric|min:0',
            'quantity_min'  => 'numeric|min:0',
            'quantity_max'  => 'numeric|min:0',
            'data_format'   => 'nullable|in:csv,xls',

            'order_by'      => 'nullable|in:' . implode(',', VoucherTransactionBulk::SORT_BY_FIELDS),
            'order_dir'     => 'nullable|in:asc,desc',
        ];
    }
}
