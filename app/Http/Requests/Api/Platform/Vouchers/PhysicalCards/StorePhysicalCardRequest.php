<?php

namespace App\Http\Requests\Api\Platform\Vouchers\PhysicalCards;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class StorePhysicalCardRequest
 * @package App\Http\Requests\Api\Platform\Vouchers\PhysicalCards
 */
class StorePhysicalCardRequest extends FormRequest
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
        return [
            'code' => 'required|string|size:12|starts_with:100|unique:physical_cards,code',
        ];
    }
}
