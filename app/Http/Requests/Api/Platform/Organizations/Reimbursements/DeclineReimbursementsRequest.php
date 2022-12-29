<?php

namespace App\Http\Requests\Api\Platform\Organizations\Reimbursements;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\Reimbursement;

class DeclineReimbursementsRequest extends BaseFormRequest
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
            'note' => 'nullable|string|min:1,2000',
            'reason' => 'nullable|string|min:1,2000',
        ];
    }
}
