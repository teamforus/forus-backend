<?php

namespace App\Http\Requests\Api\Platform\Organizations\FundProviderInvitations;

use Illuminate\Foundation\Http\FormRequest;

class IndexFundProviderInvitationRequest extends FormRequest
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
            'q' => 'nullable|string',
            'expired' => 'nullable|boolean',
            'per_page' => 'numeric|between:1,100',
        ];
    }
}
