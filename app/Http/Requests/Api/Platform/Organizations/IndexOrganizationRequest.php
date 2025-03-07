<?php

namespace App\Http\Requests\Api\Platform\Organizations;

use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\OrganizationResource;
use App\Models\Fund;
use App\Rules\DependencyRule;
use Illuminate\Validation\Rule;

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
        return [
            'role' => 'nullable|string|exists:roles,key',
            'type' => 'nullable|in:sponsor,provider',
            'is_sponsor' => 'nullable|boolean',
            'is_provider' => 'nullable|boolean',
            'is_validator' => 'nullable|boolean',
            'has_reservations' => 'nullable|boolean',
            'fund_type' => [
                'nullable',
                Rule::in(Fund::TYPES),
            ],
            'dependency' => [
                'nullable',
                new DependencyRule(OrganizationResource::DEPENDENCIES),
            ],
            ...$this->sortableResourceRules(500, [
                'created_at', 'is_sponsor', 'is_provider', 'is_validator',
                'name', 'phone', 'email', 'website',
            ]),
        ];
    }
}
