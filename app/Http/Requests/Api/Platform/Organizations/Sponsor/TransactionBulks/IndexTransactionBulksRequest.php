<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\TransactionBulks;

use App\Exports\VoucherTransactionBulksExport;
use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\VoucherTransactionBulk;
use Illuminate\Support\Arr;
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
        $fields = Arr::pluck(VoucherTransactionBulksExport::getExportFields(), 'key');

        return [
            'per_page'      => $this->perPageRule(),
            'state'         => ['nullable', Rule::in(VoucherTransactionBulk::STATES)],
            'from'          => 'nullable|date_format:Y-m-d',
            'to'            => 'nullable|date_format:Y-m-d',
            'amount_min'    => 'nullable|numeric|min:0',
            'amount_max'    => 'nullable|numeric|min:0',
            'quantity_min'  => 'nullable|numeric|min:0',
            'quantity_max'  => 'nullable|numeric|min:0',
            'data_format'   => 'nullable|in:csv,xls',
            'order_by'      => 'nullable|in:' . implode(',', VoucherTransactionBulk::SORT_BY_FIELDS),
            'order_dir'     => 'nullable|in:asc,desc',
            'fields'        => 'nullable|array',
            'fields.*'      => ['nullable', Rule::in($fields)],
        ];
    }
}
