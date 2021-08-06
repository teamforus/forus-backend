<?php

namespace App\Http\Requests\Api\Platform\Vouchers\PhysicalCardRequests;

use App\Http\Requests\BaseFormRequest;

/**
 * Class StorePhysicalCardRequestRequest
 * @package App\Http\Requests\Api\Platform\Vouchers\PhysicalCardRequests
 */
class StorePhysicalCardRequestRequest extends BaseFormRequest
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
            'address' => 'required|string|between:3,100',
            'house' => 'required|numeric|between:1,20000',
            'house_addition' => 'nullable|string|between:0,20',
            'postcode' => 'required|string|between:0,20',
            'city' => 'required|string|between:1,20',
        ];
    }
}
