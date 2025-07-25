<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;

class IndexFundRequest extends BaseFormRequest
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
            'q' => 'nullable|string|max:100',
            'tag' => 'nullable|string|exists:tags,key',
            'fund_id' => 'nullable|exists:funds,id',
            'fund_ids' => 'nullable|array',
            'fund_ids.*' => 'nullable|exists:funds,id',
            'organization_id' => 'nullable|exists:organizations,id',
            'implementation_id' => 'nullable|exists:implementations,id',
            'order_by' => 'nullable|in:created_at',
            'order_dir' => 'nullable|in:asc,desc',
            'configured' => 'nullable|bool',
            'with_archived' => 'nullable|bool',
            'with_external' => 'nullable|bool',
            'archived' => 'nullable|bool',
            'stats' => 'nullable|string|in:all,budget,product_vouchers,payout_vouchers,min',
            'per_page' => $this->perPageRule(),
            'state' => 'nullable|in:' . implode(',', [...Fund::STATES, 'active_paused_and_closed']),
            'year' => 'nullable|integer',
        ];
    }
}
