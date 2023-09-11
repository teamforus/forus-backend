<?php

namespace App\Http\Requests\Api\Platform\ProductReservations;

use App\Http\Requests\BaseFormRequest;
use App\Models\Product;
use App\Models\ProductReservation;
use Illuminate\Support\Facades\Gate;

class StoreProductReservationAddressRequest extends BaseFormRequest
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
        return [
            'address' => 'required|string|max:100',
            'city' => 'required|string|max:50',
            'street' => 'required|string|max:100',
            'house_nr' => 'required|string|max:20',
            'postal_code' => 'required|string|max:10',
        ];
    }
}
