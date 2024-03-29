<?php

namespace App\Http\Requests\Api\Platform\Organizations;

use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\OrganizationResource;
use App\Models\Fund;
use App\Rules\DependencyRule;

/**
 * @property string $dependency
 */
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
        return array_merge([
            'role'              => 'nullable|string|exists:roles,key',
            'dependency'        => [
                'nullable',
                new DependencyRule(OrganizationResource::DEPENDENCIES)
            ],
            'is_employee'       => 'nullable|boolean',
            'is_sponsor'        => 'nullable|boolean',
            'is_provider'       => 'nullable|boolean',
            'is_validator'      => 'nullable|boolean',
            'implementation'    => 'nullable|boolean',
            'has_products'      => 'nullable|boolean',
            'has_reservations'  => 'nullable|boolean',
            'fund_type'         => 'nullable|in:' . implode(',', Fund::TYPES),
        ], $this->sortableResourceRules(500, [
            'created_at', 'is_sponsor', 'is_provider', 'is_validator',
            'name', 'phone', 'email', 'website'
        ]));
    }
}
