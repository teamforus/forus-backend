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
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{address: 'required|string|between:3,100', house: 'required|numeric|between:1,20000', house_addition: 'nullable|string|between:0,20', postcode: 'required|string|between:0,20', city: 'required|string|between:1,20'}
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
