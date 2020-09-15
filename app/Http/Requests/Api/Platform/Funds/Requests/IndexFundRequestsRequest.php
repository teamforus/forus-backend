<?php

namespace App\Http\Requests\Api\Platform\Funds\Requests;

use App\Models\FundRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexFundRequestsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'per_page'      => 'numeric|between:1,100',
            'state'         => 'nullable|in:' . join(',', FundRequest::STATES),
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
