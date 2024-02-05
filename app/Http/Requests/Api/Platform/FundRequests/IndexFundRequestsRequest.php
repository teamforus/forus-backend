<?php

namespace App\Http\Requests\Api\Platform\FundRequests;

use App\Http\Requests\BaseFormRequest;

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
            'fund_id' => 'nullable|exists:funds,id',
            'archived' => 'nullable|boolean',
            ...$this->sortableResourceRules(100, [
                'id', 'fund_name', 'created_at', 'state', 'no_answer_clarification',
            ])
        ];
    }
}
