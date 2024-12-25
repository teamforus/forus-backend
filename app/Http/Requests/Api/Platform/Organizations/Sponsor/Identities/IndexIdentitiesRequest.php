<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Identities;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use Illuminate\Validation\Rule;

/**
 * @property-read Organization $organization
 */
class IndexIdentitiesRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
                Rule::in($this->organization->funds->pluck('id')->toArray())
            ],
            ...$this->sortableResourceRules(),
        ];
    }
}
