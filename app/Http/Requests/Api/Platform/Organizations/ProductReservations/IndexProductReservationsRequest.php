<?php

namespace App\Http\Requests\Api\Platform\Organizations\ProductReservations;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\ProductReservation;
use App\Scopes\Builders\TrashedQuery;

/**
 * Class IndexProductReservationsRequest
 * @property Organization $organization
 * @package App\Http\Requests\Api\Platform\Organizations\ProductReservations
 */
class IndexProductReservationsRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->isAuthenticated() &&
            $this->organization->identityCan($this->identity(), 'scan_vouchers');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $products = TrashedQuery::withTrashed($this->organization->products())->pluck('id');

        return [
            'q' => 'nullable|string',
            'to' => 'date|date_format:Y-m-d',
            'from' => 'date|date_format:Y-m-d',
            'state' => 'nullable|in:' . join(',', ProductReservation::STATES),
            'fund_id' => 'nullable|exists:funds,id',
            'per_page' => 'nullable|numeric|max:100',
            'product_id' => 'nullable|exists:products,id|in:' . $products->join(','),
            'organization_id' => 'nullable|exists:organizations,id',
        ];
    }
}
