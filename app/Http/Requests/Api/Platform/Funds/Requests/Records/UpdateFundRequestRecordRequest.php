<?php

namespace App\Http\Requests\Api\Platform\Funds\Requests\Records;

use App\Models\FundRequestRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFundRequestRecordRequest extends FormRequest
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
            'state' => [
                'required',
                Rule::in([
                    FundRequestRecord::STATE_APPROVED,
                    FundRequestRecord::STATE_DECLINED
                ])
            ],
            'note' => [
                'required_if:state,' . FundRequestRecord::STATE_DECLINED,
                'string',
                'between:0,2000'
            ]
        ];
    }
}
