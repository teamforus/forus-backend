<?php

namespace App\Http\Requests\Api\Platform\Organizations\Provider\FundUnsubscribes;

use App\Http\Requests\BaseFormRequest;
use App\Models\FundUnsubscribe;

class IndexFundUnsubscribeRequest extends BaseFormRequest
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
            'per_page'  => 'nullable|numeric|between:1,1000',
            'q'         => 'nullable|string|max:100',
            'fund_id'   => 'nullable|exists:funds,id',
            'from'      => 'nullable|date:Y-m-d',
            'to'        => 'nullable|date:Y-m-d',
            'state'     => 'nullable|in:' . implode(',', FundUnsubscribe::STATES),
            'states'    => 'nullable|array',
            'states.*'  => 'nullable|in:' . implode(',', FundUnsubscribe::STATES),
        ];
    }
}
