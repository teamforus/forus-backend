<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds;

use App\Models\FundProvider;
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
            'state' => 'nullable|in:' . join(',', FundProvider::STATES),
            'organization_id' => 'nullable|exists:organizations,id',
        ];
    }
}
