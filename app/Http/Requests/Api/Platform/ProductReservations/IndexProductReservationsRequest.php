<?php

namespace App\Http\Requests\Api\Platform\ProductReservations;

use App\Http\Requests\BaseFormRequest;
use App\Models\ProductReservation;

class IndexProductReservationsRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->isAuthenticated();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'q' => 'nullable|string',
            'to' => 'date|date_format:Y-m-d',
            'from' => 'date|date_format:Y-m-d',
            'state' => 'nullable|in:' . join(',', ProductReservation::STATES),
            'fund_id' => 'nullable|exists:funds,id',
            'per_page' => 'nullable|numeric|max:100',
            'archived' => 'nullable|boolean',
            'product_id' => 'nullable|exists:products,id',
            'organization_id' => 'nullable|exists:organizations,id',
        ];
    }
}
