<?php

namespace App\Http\Requests\Api\Platform\FundRequests;

use App\Http\Requests\BaseFormRequest;
use App\Models\FundRequest;

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
            'fund_id'       => 'nullable|exists:funds,id',
            'order_by'      => 'nullable|in:id,fund_name,created_at,note,state,no_answer_clarification',
            'order_dir'     => 'nullable|in:asc,desc',
        ];
    }
}
