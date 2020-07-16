<?php

namespace App\Http\Requests\Api\Platform\Vouchers;

use Illuminate\Foundation\Http\FormRequest;

class RequestPhysicalCardRequest extends FormRequest
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
            'postcode'      => 'required|string|max:50',
            'house_number'  => 'required|string|max:50',
        ];
    }
}
