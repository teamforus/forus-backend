<?php

namespace App\Http\Requests\Api\Platform\Organizations;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationRolesRequest extends FormRequest
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
            'is_sponsor' => 'nullable|boolean',
            'is_provider' => 'nullable|boolean',
            'is_validator' => 'nullable|boolean',
            'validator_auto_accept_funds' => 'nullable|boolean',
        ];
    }
}
