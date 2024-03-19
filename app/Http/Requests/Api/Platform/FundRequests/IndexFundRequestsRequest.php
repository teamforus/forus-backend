<?php

namespace App\Http\Requests\Api\Platform\FundRequests;

use App\Http\Requests\BaseFormRequest;

class IndexFundRequestsRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return (mixed|string)[]
     *
     * @psalm-return array{fund_id: 'nullable|exists:funds,id'|mixed, archived: 'nullable|boolean'|mixed,...}
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
