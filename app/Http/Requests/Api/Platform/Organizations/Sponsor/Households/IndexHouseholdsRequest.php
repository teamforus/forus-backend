<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Households;

use App\Http\Requests\BaseFormRequest;
use App\Models\Household;
use App\Models\Organization;
use Illuminate\Validation\Rule;

/**
 * @property Organization $organization
 */
class IndexHouseholdsRequest extends BaseFormRequest
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
            'q' => $this->qRule(),
            'fund_id' => [
                'nullable',
                Rule::exists('funds', 'id')->where('organization_id', $this->organization->id),
            ],
            'living_arrangement' => [
                'nullable',
                Rule::in(Household::LIVING_ARRANGEMENTS),
            ],
            ...$this->orderByRules('created_at', 'updated_at'),
        ];
    }
}
