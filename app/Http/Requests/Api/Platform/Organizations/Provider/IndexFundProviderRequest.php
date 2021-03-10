<?php

namespace App\Http\Requests\Api\Platform\Organizations\Provider;

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
            'dismissed'         => 'nullable|boolean',
            'allow_budget'      => 'nullable|boolean',
            'allow_products'    => 'nullable|in:1,0,some',
            'per_page'          => 'numeric|between:1,100',
            'q'                 => 'nullable|string',
            'export_format'     => 'nullable|in:csv,xls'
        ];
    }
}
