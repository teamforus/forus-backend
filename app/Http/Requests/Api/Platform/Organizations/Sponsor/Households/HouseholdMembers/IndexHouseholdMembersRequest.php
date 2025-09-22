<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Households\HouseholdMembers;

use App\Http\Requests\BaseFormRequest;

class IndexHouseholdMembersRequest extends BaseFormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled in the controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            ...$this->sortableResourceRules(100, ['created_at', 'updated_at']),
        ];
    }
}
