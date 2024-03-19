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
     * Get the validation rules that apply to the request.
     *
     * @return (array|string)[]
     *
     * @psalm-return array{fund_id: array, expired: 'nullable|boolean', archived: 'nullable|boolean', deactivated: 'nullable|boolean', identity_address: 'nullable|exists:identities,address', implementation_id: string, per_page: string}
     */
    public function rules(): array
    {
        $implementations = $this->organization->implementations()->pluck('implementations.id');

        return [
            'fund_id' => $this->fundIdRule(),
            'expired' => 'nullable|boolean',
            'archived' => 'nullable|boolean',
            'deactivated' => 'nullable|boolean',
            'identity_address' => 'nullable|exists:identities,address',
            'implementation_id' => 'nullable|exists:implementations,id|in:' . $implementations->join(','),
            'per_page' => $this->perPageRule(),
        ];
    }

    /**
     * @return (\Illuminate\Validation\Rules\In|string)[]
     *
     * @psalm-return list{'nullable', \Illuminate\Validation\Rules\In}
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
