<?php

namespace App\Http\Requests\Api\Platform\Organizations\ValidatorOrganizations;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class StoreValidatorOrganizationsRequest
 * @property Organization $organization
 * @package App\Http\Requests\Api\Platform\Organizations\ValidatorOrganizations
 */
class StoreValidatorOrganizationsRequest extends FormRequest
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
        $organization = $this->organization;

        $existingValidators = $organization ? $organization->organization_validators()->pluck(
            'validator_organization_id'
        )->toArray() : [];

        $existingValidators[] = $organization->id ?? null;

        return [
            'organization_id' => [
                'required',
                Rule::in(Organization::whereIsValidator(true)->whereNotIn(
                    'id', $existingValidators
                )->pluck('id'))
            ]
        ];
    }
}
