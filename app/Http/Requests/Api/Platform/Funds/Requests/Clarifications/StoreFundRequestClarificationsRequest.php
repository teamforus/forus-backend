<?php

namespace App\Http\Requests\Api\Platform\Funds\Requests\Clarifications;

use App\Models\FundRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class StoreFundRequestClarificationsRequest
 * @property FundRequest $fund_request
 * @package App\Http\Requests\Api\Platform\Funds\Requests\Records\Clarifications
 */
class StoreFundRequestClarificationsRequest extends FormRequest
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
            'fund_request_record_id' => [
                'required',
                Rule::in($this->fund_request->records()->pluck('id')->toArray())
            ],
            'question' => 'required|string|between:2,2000',
        ];
    }
}
