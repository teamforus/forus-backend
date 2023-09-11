<?php

namespace App\Http\Requests\Api\Platform\ProductReservations;

use App\Models\Product;
use App\Models\ProductReservation;
use Illuminate\Support\Facades\Gate;

class StoreProductReservationRequest extends StoreProductReservationClientRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->isAuthenticated() && Gate::allows('create', ProductReservation::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $product_required = Product::find($this->input('product_id'))->reservation_address_is_required;

        return array_merge(parent::rules(), [
            'city' => [
                $product_required ? 'required' : 'nullable',
                'string',
                'max:50'
            ],
            'street' => [
                $product_required ? 'required' : 'nullable',
                'string',
                'max:100'
            ],
            'house_nr' => [
                $product_required ? 'required' : 'nullable',
                'string',
                'max:20'
            ],
            'postal_code' => [
                $product_required ? 'required' : 'nullable',
                'string',
                'max:10'
            ],
        ]);
    }
}
