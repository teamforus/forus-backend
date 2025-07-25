<?php

namespace App\Http\Requests\Api\Platform\Organizations\ProductReservations;

use App\Exports\ProductReservationsExport;
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
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return
            $this->isAuthenticated() &&
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
            'state' => 'nullable|in:' . implode(',', [...ProductReservation::STATES, 'expired']),
            'fund_id' => 'nullable|exists:funds,id',
            'per_page' => 'nullable|numeric|max:100',
            'product_id' => 'nullable|exists:products,id|in:' . $products->join(','),
            'organization_id' => 'nullable|exists:organizations,id',
            'archived' => 'nullable|boolean',
            ...$this->exportableResourceRules(ProductReservationsExport::getExportFieldsRaw()),
            ...$this->sortableResourceRules(columns: [
                'created_at', 'code', 'product', 'price', 'amount_extra', 'customer',
                'created_at', 'state',
            ]),
        ];
    }
}
