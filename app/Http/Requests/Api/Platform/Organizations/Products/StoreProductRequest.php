<?php

namespace App\Http\Requests\Api\Platform\Organizations\Products;

class StoreProductRequest extends BaseProductRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $unlimited_stock = $this->input('unlimited_stock', false);

        return [
            ...$this->baseProductRules((string) $this->input('price_type')),
            ...$this->reservationRules(),

            'unlimited_stock' => 'boolean',
            'total_amount' => [$unlimited_stock ? null : 'required', 'numeric', 'min:1'],
        ];
    }
}
