<?php

namespace App\Http\Requests\Api\Platform\Funds;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexFundsRequest extends FormRequest
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
            'per_page'        => [
                'nullable',
                'numeric',
                'between:1,100',
            ],
            'q'               => [
                'nullable',
                'string',
            ],
            'tag'             => [
                'nullable',
                'string',
                'exists:tags,key',
            ],
            'fund_id'         => [
                'nullable',
                'exists:funds,id',
            ],
            'organization_id' => [
                'nullable',
                'exists:organizations,id'
            ],
            'state' => [
                'nullable',
                Rule::in([
                    'active_and_closed'
                ])
            ]
        ];
    }
}
