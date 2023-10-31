<?php

namespace App\Http\Requests\Api\Platform\ProductReservations;

class ValidateProductReservationAddressRequest extends StoreProductReservationRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return $this->addressRules($this->getProduct());
    }
}
