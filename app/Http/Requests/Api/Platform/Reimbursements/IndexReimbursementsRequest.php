<?php

namespace App\Http\Requests\Api\Platform\Reimbursements;

use App\Http\Requests\BaseFormRequest;
use App\Models\Reimbursement;

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
            'per_page' => $this->perPageRule(),
            'state' => 'nullable|in:' . implode(',', Reimbursement::STATES),
            'expired' => 'nullable|boolean',
            'fund_id' => 'nullable|exists:funds,id',
        ];
    }
}
