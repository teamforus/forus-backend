<?php

namespace App\Http\Requests\Api\Platform\Organizations;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationBusinessTypeRequest extends FormRequest
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
            'business_type_id' => 'required|exists:business_types,id',
        ];
    }
}
