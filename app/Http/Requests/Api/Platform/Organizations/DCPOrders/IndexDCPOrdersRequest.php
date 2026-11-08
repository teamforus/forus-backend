<?php

namespace App\Http\Requests\Api\Platform\Organizations\DCPOrders;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Searches\DCPOrderSearch;
use App\Services\DCPService\Models\DCPOrder;
use Illuminate\Validation\Rule;

/**
 * @property Organization|null $organization
 */
class IndexDCPOrdersRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'q' => 'nullable|string|max:100',
            'state' => [
                'nullable',
                Rule::in([
                    'approved', DCPOrder::STATE_PENDING, DCPOrder::STATE_SENT, DCPOrder::STATE_ERROR,
                    DCPOrder::STATE_CLOSED, DCPOrder::STATE_CANCELED,
                ]),
            ],
            'dcp_order_bulk_id' => [
                'nullable',
                Rule::exists('dcp_order_bulks', 'id')->where('organization_id', $this->organization->id),
            ],
            'state_group' => ['nullable', Rule::in(DCPOrderSearch::STATE_GROUPS)],
            'awaiting_approval' => 'nullable|boolean',
            'with_counts' => 'nullable|boolean',
            'per_page' => $this->perPageRule(),
        ];
    }
}
