<?php

namespace App\Http\Requests\Api\Platform\Organizations;

use App\Http\Requests\BaseFormRequest;

class IndexOrganizationRequest extends BaseFormRequest
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
            'role' => 'nullable|string|exists:roles,key',
            'type' => 'nullable|in:sponsor,provider',
            'is_sponsor' => 'nullable|boolean',
            'is_provider' => 'nullable|boolean',
            'is_validator' => 'nullable|boolean',
            'has_reservations' => 'nullable|boolean',
            ...$this->sortableResourceRules(500, [
                'created_at', 'is_sponsor', 'is_provider', 'is_validator',
                'name', 'phone', 'email', 'website',
            ]),
        ];
    }
}
