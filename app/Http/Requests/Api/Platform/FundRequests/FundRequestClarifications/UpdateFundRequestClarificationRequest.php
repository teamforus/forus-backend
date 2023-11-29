<?php

namespace App\Http\Requests\Api\Platform\FundRequests\FundRequestClarifications;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFundRequestClarificationRequest extends FormRequest
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
            'answer' => 'required:files|nullable|between:0,2000',
            'files' => 'nullable|array',
            'files.*' => 'exists:files,uid',
        ];
    }
}
