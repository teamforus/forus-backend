<?php

namespace App\Http\Requests\Api\Platform\Organizations\FundForms;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use Illuminate\Validation\Rule;

/**
 * @property Organization $organization
 */
class IndexFundFormRequest extends BaseFormRequest
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
            'state' => 'nullable|in:active,archived',
            'order_by' => 'nullable|in:created_at',
            'order_dir' => 'nullable|in:asc,desc',
            'implementation_id' => [
                'nullable',
                Rule::exists('implementations', 'id')->where('organization_id', $this->organization->id),
            ],
            ...$this->sortableResourceRules(100, ['created_at']),
        ];
    }
}
