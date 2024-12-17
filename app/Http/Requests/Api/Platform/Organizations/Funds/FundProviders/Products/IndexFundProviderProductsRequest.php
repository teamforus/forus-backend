<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds\FundProviders\Products;

use Illuminate\Foundation\Http\FormRequest;

class IndexFundProviderProductsRequest extends FormRequest
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
            'per_page' => 'nullable|numeric|between:1,100',
        ];
    }
}
