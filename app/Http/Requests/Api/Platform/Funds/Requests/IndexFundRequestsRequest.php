<?php

namespace App\Http\Requests\Api\Platform\Funds\Requests;

use App\Exports\FundRequestsExport;
use App\Http\Requests\BaseFormRequest;
use App\Models\FundRequest;
use App\Models\Organization;
use Illuminate\Validation\Rule;

/**
 * @property Organization $organization
 */
class IndexFundRequestsRequest extends BaseFormRequest
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
            'state' => 'nullable|in:' . implode(',', FundRequest::STATES),
            'employee_id' => 'nullable|exists:employees,id',
            'assigned' => 'nullable|boolean',
            'from' => 'nullable|date:Y-m-d',
            'to' => 'nullable|date:Y-m-d',
            'state_group' => 'nullable|in:all,pending,assigned,resolved',
            'identity_id' => 'nullable|exists:identities,id',
            'fund_id' => [
                'nullable',
                Rule::exists('funds', 'id')->where('organization_id', $this->organization->id),
            ],
            ...$this->sortableResourceRules(100, [
                'id', 'fund_name', 'created_at', 'note', 'state', 'requester_email', 'assignee_email',
            ]),
            ...$this->exportableResourceRules(FundRequestsExport::getExportFieldsRaw()),
        ];
    }
}
