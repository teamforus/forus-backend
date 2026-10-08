<?php

namespace App\Http\Requests\Api\Platform\Funds\Requests\Clarifications;

use App\Http\Requests\BaseFormRequest;

class CloseFundRequestClarificationsRequest extends BaseFormRequest
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
            'note' => 'nullable|string|between:2,2000',
            'notify_requester' => 'nullable|boolean',
        ];
    }
}
