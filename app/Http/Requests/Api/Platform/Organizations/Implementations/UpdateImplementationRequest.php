<?php

namespace App\Http\Requests\Api\Platform\Organizations\Implementations;

use Illuminate\Foundation\Http\FormRequest;

class UpdateImplementationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return true
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{has_more_info_url: 'nullable|boolean', more_info_url: 'nullable|string|max:50', description_steps: 'nullable|string|max:4000', title: 'nullable|string|max:50', description: 'nullable|string|max:4000', digid_shared_secret: 'nullable|string|max:100', digid_app_id: 'required_with:digid_shared_secret|nullable|string|max:100', digid_a_select_server: 'required_with:digid_shared_secret|nullable|string|max:100', digid_enabled: 'nullable|boolean', email_from_address: 'nullable|email', email_from_name: 'nullable|string|max:50'}
     */
    public function rules(): array
    {
        return [
            // cms
            'has_more_info_url'     => 'nullable|boolean',
            'more_info_url'         => 'nullable|string|max:50',
            'description_steps'     => 'nullable|string|max:4000',
            'title'                 => 'nullable|string|max:50',
            'description'           => 'nullable|string|max:4000',

            // digid
            'digid_shared_secret'   => 'nullable|string|max:100',
            'digid_app_id'          => 'required_with:digid_shared_secret|nullable|string|max:100',
            'digid_a_select_server' => 'required_with:digid_shared_secret|nullable|string|max:100',
            'digid_enabled'         => 'nullable|boolean',

            // email
            'email_from_address'    => 'nullable|email',
            'email_from_name'       => 'nullable|string|max:50',
        ];
    }
}
