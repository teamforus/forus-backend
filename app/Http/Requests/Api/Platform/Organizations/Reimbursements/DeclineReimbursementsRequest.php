<?php

namespace App\Http\Requests\Api\Platform\Organizations\Reimbursements;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\Reimbursement;

class DeclineReimbursementsRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{note: 'nullable|string|min:1,2000', reason: 'nullable|string|min:1,2000'}
     */
    public function rules(): array
    {
        return [
            'note' => 'nullable|string|min:1,2000',
            'reason' => 'nullable|string|min:1,2000',
        ];
    }
}
