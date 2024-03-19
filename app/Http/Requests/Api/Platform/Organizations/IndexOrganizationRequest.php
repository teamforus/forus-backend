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
     * Get the validation rules that apply to the request.
     *
     * @return ((DependencyRule|string)[]|mixed|string)[]
     *
     * @psalm-return array{role: 'nullable|string|exists:roles,key'|mixed, dependency: list{'nullable', DependencyRule}|mixed, is_employee: 'nullable|boolean'|mixed, is_sponsor: 'nullable|boolean'|mixed, is_provider: 'nullable|boolean'|mixed, is_validator: 'nullable|boolean'|mixed, implementation: 'nullable|boolean'|mixed, has_products: 'nullable|boolean'|mixed, has_reservations: 'nullable|boolean'|mixed, fund_type: mixed|string,...}
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
