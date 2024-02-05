<?php

namespace App\Http\Requests\Api\Platform\ProductReservations;

class ValidateProductReservationFieldsRequest extends StoreProductReservationRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return $this->baseRules($this->getProduct());
    }
}
