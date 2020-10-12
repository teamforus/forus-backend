<?php

namespace App\Http\Requests\Api\Platform\Organizations;

use App\Http\Resources\OrganizationResource;
use App\Rules\DependencyRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class IndexOrganizationRequest
 * @property string $dependency
 * @package App\Http\Requests\Api\Platform\Organizations
 */
class IndexOrganizationRequest extends FormRequest
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
            'per_page'  => 'nullable|numeric|between:1,100',
            'role' => [
                'nullable', 'string', 'exists:roles,key'
            ],
            'dependency' => ['nullable', new DependencyRule(
                OrganizationResource::DEPENDENCIES
            )],
            'is_employee'   => 'nullable|boolean',
            'is_sponsor'    => 'nullable|boolean',
            'is_provider'   => 'nullable|boolean',
            'is_validator'  => 'nullable|boolean',
        ];
    }
}
