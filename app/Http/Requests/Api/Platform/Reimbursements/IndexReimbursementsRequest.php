<?php

namespace App\Http\Requests\Api\Platform\Reimbursements;

use App\Http\Requests\BaseFormRequest;
use App\Models\Reimbursement;

class IndexReimbursementsRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{per_page: string, state: string, archived: 'nullable|boolean', fund_id: 'nullable|exists:funds,id'}
     */
    public function rules(): array
    {
        return [
            'per_page' => $this->perPageRule(),
            'state' => 'nullable|in:' . implode(',', Reimbursement::STATES),
            'archived' => 'nullable|boolean',
            'fund_id' => 'nullable|exists:funds,id',
        ];
    }
}
