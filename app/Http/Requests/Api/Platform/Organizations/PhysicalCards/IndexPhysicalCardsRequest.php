<?php

namespace App\Http\Requests\Api\Platform\Organizations\PhysicalCards;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use Illuminate\Validation\Rule;

/**
 * @property Organization $organization
 */
class IndexPhysicalCardsRequest extends BaseFormRequest
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
            ...$this->sortableResourceRules(),
            'fund_id' => [
                'nullable',
                Rule::exists('funds', 'id')->where('organization_id', $this->organization->id),
            ],
            'physical_card_type_id' => [
                'nullable',
                Rule::exists('physical_card_types', 'id')->where('organization_id', $this->organization->id),
            ],
        ];
    }
}
