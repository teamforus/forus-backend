<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\TransactionBulks;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\VoucherTransactionBulk;

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
            'per_page' => $this->perPageRule(),
            'order_by' => 'nullable|in:' . implode(',', VoucherTransactionBulk::SORT_BY_FIELDS),
            'order_dir' => 'nullable|in:asc,desc',
        ];
    }
}
