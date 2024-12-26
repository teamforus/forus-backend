<?php

namespace App\Http\Requests\Api\Platform\Organizations\Reimbursements;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\Reimbursement;
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
        $implementations = $this->organization->implementations()->pluck('implementations.id');

        return [
            'fund_id' => $this->fundIdRule(),
            'from' => 'nullable|date_format:Y-m-d',
            'to' => 'nullable|date_format:Y-m-d',
            'amount_min' => 'nullable|numeric',
            'amount_max' => 'nullable|numeric',
            'expired' => 'nullable|boolean',
            'archived' => 'nullable|boolean',
            'deactivated' => 'nullable|boolean',
            'state' => 'nullable|in:' . implode(',', Reimbursement::STATES),
            'identity_address' => 'nullable|exists:identities,address',
            'implementation_id' => 'nullable|exists:implementations,id|in:' . $implementations->join(','),
            ...$this->sortableResourceRules()
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
