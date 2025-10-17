<?php

namespace App\Http\Requests\Api\Platform\Organizations\PhysicalCardTypes;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use Illuminate\Validation\Rule;

/**
 * @property Organization $organization
 */
class IndexPhysicalCardTypeRequest extends BaseFormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'fund_id' => [
                'nullable',
                Rule::exists('funds', 'id')->where('organization_id', $this->organization->id),
            ],
            ...$this->sortableResourceRules(),
        ];
    }
}
