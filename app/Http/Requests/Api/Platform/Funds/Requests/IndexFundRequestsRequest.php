<?php

namespace App\Http\Requests\Api\Platform\Funds\Requests;

use App\Http\Requests\BaseFormRequest;
use App\Models\FundRequest;
use Illuminate\Validation\Rule;

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
            'per_page'      => 'numeric|between:1,100',
            'state'         => 'nullable|in:' . implode(',', FundRequest::STATES),
            'employee_id'   => 'nullable|exists:employees,id',
            'from'          => 'nullable|date:Y-m-d',
            'to'            => 'nullable|date:Y-m-d',
            'sort_by'       => [
                'nullable',
                Rule::in([
                    'created_at', 'note'
                ])
            ],
            'sort_order'    => [
                'nullable',
                Rule::in([
                    'asc', 'desc'
                ])
            ],
        ];
    }
}
