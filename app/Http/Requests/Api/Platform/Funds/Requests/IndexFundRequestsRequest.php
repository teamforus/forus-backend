<?php

namespace App\Http\Requests\Api\Platform\Funds\Requests;

use App\Http\Requests\BaseFormRequest;
use App\Models\FundRequest;

/**
 * Class IndexFundRequestsRequest
 * @package App\Http\Requests\Api\Platform\Funds\Requests
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
            'per_page'      => 'numeric|between:1,100',
            'state'         => 'nullable|in:' . implode(',', FundRequest::STATES),
            'employee_id'   => 'nullable|exists:employees,id',
            'assigned'      => 'nullable|boolean',
            'from'          => 'nullable|date:Y-m-d',
            'to'            => 'nullable|date:Y-m-d',
            'order_by'      => 'nullable|in:id,fund_name,created_at,note,state,requester_email,assignee_email',
            'order_dir'     => 'nullable|in:asc,desc',
            'export_format' => 'nullable|in:csv,xls',
        ];
    }
}
