<?php

namespace App\Http\Requests\Api\Platform\Funds;

use Illuminate\Foundation\Http\FormRequest;

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
            ]
        ];
    }
}
