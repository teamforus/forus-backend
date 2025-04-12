<?php

namespace App\Http\Requests\Api\Platform\EmailLogs;

use App\Http\Requests\BaseFormRequest;

class IndexEmailLogsRequest extends BaseFormRequest
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
        return [
            'q' => $this->qRule(),
            'per_page' => $this->perPageRule(),
            'identity_id' => 'nullable|exists:identities,id',
            'fund_request_id' => 'nullable|exists:fund_requests,id',
        ];
    }
}
