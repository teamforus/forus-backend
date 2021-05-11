<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds;

use App\Http\Requests\BaseFormRequest;

/**
 * Class FinanceRequest
 * @package App\Http\Requests\Api\Platform\Organizations\Funds
 */
class FinanceRequest extends BaseFormRequest
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
            'filters'           => 'nullable|bool',
            'type'              => 'nullable|in:year,quarter,month',
            'type_value'        => 'required_with:type|date_format:Y-m-d',
            'fund_ids'          => 'nullable|array',
            'fund_ids.*'        => 'required|exists:funds,id',
            'postcodes'         => 'nullable|array',
            'postcodes.*'       => 'nullable|string|max:100',
            'provider_ids'      => 'nullable|array',
            'provider_ids.*'    => 'nullable|exists:organizations,id',
            'product_category_ids'   => 'nullable|array',
            'product_category_ids.*' => 'nullable|exists:product_categories,id',
        ];
    }
}
