<?php

namespace App\Http\Requests\Api\Platform\Organizations;

use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\OrganizationResource;
use App\Models\Fund;
use App\Rules\DependencyRule;

/**
 * Class IndexOrganizationRequest
 * @property string $dependency
 * @package App\Http\Requests\Api\Platform\Organizations
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
        return [
            'per_page'          => 'nullable|numeric|between:1,500',
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
            'order_by'          => 'nullable|in:created_at,sponsor',
            'order_by_dir'      => 'nullable|in:asc,desc',
        ];
    }
}
