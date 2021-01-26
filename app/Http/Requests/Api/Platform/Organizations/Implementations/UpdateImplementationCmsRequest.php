<?php

namespace App\Http\Requests\Api\Platform\Organizations\Implementations;

use Illuminate\Foundation\Http\FormRequest;

class UpdateImplementationCmsRequest extends FormRequest
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
            'has_more_info_url'         => 'nullable|boolean',
            'more_info_url'             => 'nullable|string|max:50',
            'description_steps'         => 'nullable|string|max:10000',
            'description_providers'     => 'nullable|string|max:10000',
            'title'                     => 'nullable|string|max:50',
            'description'               => 'nullable|string|max:4000',
            'informal_communication'    => 'nullable|boolean',
        ];
    }
}
