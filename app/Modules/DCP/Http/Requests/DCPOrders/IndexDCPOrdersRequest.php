<?php

namespace App\Modules\DCP\Http\Requests\DCPOrders;

use App\Http\Requests\BaseFormRequest;
use App\Modules\DCP\Enums\DCPOrderState;
use App\Modules\DCP\Enums\DCPOrderStateGroup;
use App\Modules\DCP\Models\DCPOrder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class IndexDCPOrdersRequest extends BaseFormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('viewAny', [DCPOrder::class, $this->getRouteOrganization()]);
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'q' => $this->qRule(),
            'state' => ['nullable', Rule::enum(DCPOrderState::class)],
            'state_group' => ['nullable', Rule::enum(DCPOrderStateGroup::class)],
            'dcp_order_bulk_id' => ['nullable', 'integer', 'min:1'],
            'with_counts' => ['sometimes', 'boolean'],
            'page' => $this->pageRule(),
            'per_page' => $this->perPageRule(),
        ];
    }
}
