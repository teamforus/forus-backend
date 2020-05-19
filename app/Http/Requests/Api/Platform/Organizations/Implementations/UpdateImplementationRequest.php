<?php

namespace App\Http\Requests\Api\Platform\Organizations\Implementations;

use Illuminate\Foundation\Http\FormRequest;

class UpdateImplementationRequest extends FormRequest
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
            'email_from_address'    => 'nullable|string|max:50',
            'email_from_name'       => 'nullable|string|max:50',
            'has_more_info_url'     => 'nullable|boolean',
            'more_info_url'         => 'nullable|string|max:50',
            'description_steps'     => 'nullable|string|max:4000',
            'title'                 => 'nullable|string|max:50',
            'description'           => 'nullable|string|max:4000',
            'digid_app_id'          => 'nullable|string|max:100',
            'digid_shared_secret'   => 'nullable|string|max:100',
            'digid_a_select_server' => 'nullable|string|max:100',
            'digid_enabled'         => 'nullable|boolean',
        ];
    }
}
