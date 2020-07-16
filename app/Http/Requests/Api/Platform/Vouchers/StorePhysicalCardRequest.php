<?php

namespace App\Http\Requests\Api\Platform\Vouchers;

use Illuminate\Foundation\Http\FormRequest;

class StorePhysicalCardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'code' => 'required|string|min:16|max:16|starts_with:1001'
        ];
    }
}
