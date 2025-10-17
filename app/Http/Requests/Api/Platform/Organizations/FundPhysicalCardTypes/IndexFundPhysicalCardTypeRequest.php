<?php

namespace App\Http\Requests\Api\Platform\Organizations\FundPhysicalCardTypes;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use Illuminate\Validation\Rule;

/**
 * @property Organization $organization
 */
class IndexFundPhysicalCardTypeRequest extends BaseFormRequest
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
            'fund_id' => [
                'nullable',
                Rule::exists('funds', 'id')->where('organization_id', $this->organization->id),
            ],
            'physical_card_type_id' => [
                'nullable',
                Rule::exists('physical_card_types', 'id')->where('organization_id', $this->organization->id),
            ],
            ...$this->sortableResourceRules(),
        ];
    }
}
