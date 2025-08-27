<?php

namespace App\Http\Requests\Api\Platform\FundRequests\FundRequestClarifications;

use App\Http\Requests\BaseFormRequest;
use App\Models\FundRequestClarification;
use Illuminate\Validation\Rule;

/**
 * @property FundRequestClarification $fund_request_clarification
 */
class UpdateFundRequestClarificationRequest extends BaseFormRequest
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
            'answer' => [
                $this->fund_request_clarification->text_requirement === 'required' ? 'required' : 'nullable',
                'between:0,2000',
            ],
            'files' => [
                $this->fund_request_clarification->files_requirement === 'required' ? 'required' : 'nullable',
                'array',
            ],
            'files.*' => [
                'required',
                Rule::exists('files', 'uid')
                    ->whereNull('fileable_id')
                    ->whereNull('fileable_type')
                    ->where('type', 'fund_request_clarification_proof')
                    ->where('identity_address', $this->auth_address()),
            ],
        ];
    }
}
