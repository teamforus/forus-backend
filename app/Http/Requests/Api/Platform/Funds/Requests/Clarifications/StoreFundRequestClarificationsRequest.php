<?php

namespace App\Http\Requests\Api\Platform\Funds\Requests\Clarifications;

use App\Http\Requests\BaseFormRequest;
use App\Models\FundRequest;
use Illuminate\Validation\Rule;

/**
 * @property FundRequest $fund_request
 */
class StoreFundRequestClarificationsRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return ((\Illuminate\Validation\Rules\In|string)[]|string)[]
     *
     * @psalm-return array{fund_request_record_id: list{'required', \Illuminate\Validation\Rules\In}, question: 'required|string|between:2,2000'}
     */
    public function rules(): array
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
