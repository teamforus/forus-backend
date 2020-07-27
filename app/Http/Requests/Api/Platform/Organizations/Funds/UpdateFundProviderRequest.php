<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds;

use App\Models\FundProvider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class UpdateFundProviderRequest
 * @property FundProvider|null $fund_provider
 * @package App\Http\Requests\Api\Platform\Organizations\Funds
 */
class UpdateFundProviderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $fundProvider = $this->fund_provider;

        return [
            'allow_products' => 'nullable|boolean',
            'allow_budget' => 'nullable|boolean',
            'dismissed' => 'nullable|boolean',
            'enable_products' => 'nullable|array',
            'enable_products.*' => [
                'required',
                Rule::exists('products', 'id')->where(
                    'organization_id', $fundProvider->organization_id
                )
            ],
            'disable_products' => 'nullable|array',
            'disable_products.*' => [
                'required',
                Rule::exists('products', 'id')->where(
                    'organization_id', $fundProvider->organization_id
                )
            ]
        ];
    }
}
