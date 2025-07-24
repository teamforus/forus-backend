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
        $product = $this->getProduct();

        return [
            ...$this->baseRules($product),
            'user_note_skip' => 'sometimes|boolean',
            ...$this->boolean('user_note_skip') ? [
                'user_note' => 'nullable|string',
            ] : [],
        ];
    }
}
