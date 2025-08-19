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
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->isAuthenticated();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $oneMustBeRequired = function ($attribute, $value, $fail) {
            $text = $this->input('text_requirement');
            $files = $this->input('files_requirement');

            if ($text !== 'required' && $files !== 'required') {
                $fail('Minimaal één van "Tekstuele uitleg" of "Uploaden bestand" moet verplicht zijn.');
            }
        };

        return [
            'fund_request_record_id' => [
                'required',
                Rule::in($this->fund_request->records()->pluck('id')->toArray()),
            ],
            'question' => 'required|string|between:2,2000',
            'text_requirement' => ['required', 'string', Rule::in(['no', 'optional', 'required']), $oneMustBeRequired],
            'files_requirement' => ['required', 'string', Rule::in(['no', 'optional', 'required']), $oneMustBeRequired],
        ];
    }
}
