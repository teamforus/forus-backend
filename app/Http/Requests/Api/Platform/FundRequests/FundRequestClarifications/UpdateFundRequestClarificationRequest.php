<?php

namespace App\Http\Requests\Api\Platform\FundRequests\FundRequestClarifications;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

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
            'answer' => 'required|between:0,2000',
            'files' => 'required|array',
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
