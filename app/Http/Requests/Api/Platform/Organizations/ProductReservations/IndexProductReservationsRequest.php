<?php

namespace App\Http\Requests\Api\Platform\Organizations\ProductReservations;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\ProductReservation;
use App\Scopes\Builders\TrashedQuery;

/**
 * @property Organization $organization
 */
class IndexProductReservationsRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{q: 'nullable|string', to: 'date|date_format:Y-m-d', from: 'date|date_format:Y-m-d', state: string, fund_id: 'nullable|exists:funds,id', per_page: 'nullable|numeric|max:100', product_id: string, organization_id: 'nullable|exists:organizations,id', archived: 'nullable|boolean'}
     */
    public function rules(): array
    {
        $products = TrashedQuery::withTrashed($this->organization->products())->pluck('id');

        return [
            'q' => 'nullable|string',
            'to' => 'date|date_format:Y-m-d',
            'from' => 'date|date_format:Y-m-d',
            'state' => 'nullable|in:' . join(',', [...ProductReservation::STATES, 'expired']),
            'fund_id' => 'nullable|exists:funds,id',
            'per_page' => 'nullable|numeric|max:100',
            'product_id' => 'nullable|exists:products,id|in:' . $products->join(','),
            'organization_id' => 'nullable|exists:organizations,id',
            'archived' => 'nullable|boolean',
        ];
    }
}
