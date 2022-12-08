<?php

namespace App\Http\Requests\Api\Platform\Organizations\Reimbursements;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Scopes\Builders\FundQuery;
use Illuminate\Validation\Rule;

/**
 * @property-read Organization $organization
 */
class IndexReimbursementsRequest extends BaseFormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'fund_id' => $this->fundIdRule(),
            'per_page' => $this->perPageRule(),
            'expired' => 'nullable|boolean',
        ];
    }

    /**
     * @return array
     */
    protected function fundIdRule(): array
    {
        return [
            'nullable',
            Rule::in(FundQuery::whereIsInternalAndConfigured(
                $this->organization->funds()
            )->pluck('id')->toArray())
        ];
    }
}
