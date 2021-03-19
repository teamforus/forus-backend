<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds;

use Illuminate\Foundation\Http\FormRequest;

class IndexFundProviderRequest extends FormRequest
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
            'q'  => 'nullable|string|max:50',
            'organization_id' => 'nullable|exists:organizations,id',
            'approved_or_has_transactions' => 'nullable|boolean'
        ];
    }
}
