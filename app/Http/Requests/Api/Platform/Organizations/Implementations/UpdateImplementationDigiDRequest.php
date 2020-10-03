<?php

namespace App\Http\Requests\Api\Platform\Organizations\Implementations;

use Illuminate\Foundation\Http\FormRequest;

class UpdateImplementationDigiDRequest extends FormRequest
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
            'digid_app_id'          => 'required_with:digid_shared_secret|nullable|string|max:100',
            'digid_shared_secret'   => 'required|string|max:100',
            'digid_a_select_server' => 'required_with:digid_shared_secret|nullable|string|max:100',
            'digid_enabled'         => 'nullable|boolean',
        ];
    }
}
