<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds;

use App\Models\FundProvider;
use App\Rules\FundProviderProductSubsidyRule;
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
        return array_merge(
            $this->baseRules(),
            $this->enabledProductsRules(),
            $this->disabledProductsRules()
        );
    }

    /**
     * @return array
     */
    private function baseRules(): array {
        return [
            'allow_products' => 'nullable|boolean',
            'allow_budget' => 'nullable|boolean',
            'dismissed' => 'nullable|boolean',
        ];
    }

    /**
     * @return array
     */
    private function enabledProductsRules(): array {
        return array_merge([
            'enable_products' => 'nullable|array',
            'enable_products.*.id' => ['required', 'numeric', Rule::exists('products', 'id')->where(
                'organization_id', $this->fund_provider->organization_id
            )],
        ], $this->fund_provider->fund->isTypeSubsidy() ? [
            'enable_products.*.amount' => 'required|numeric|min:0',
            'enable_products.*.limit_total' => 'required|numeric|min:0',
            'enable_products.*.limit_total_unlimited' => 'nullable|boolean',
            'enable_products.*.limit_per_identity' => 'required|numeric|min:0',
            'enable_products.*' => new FundProviderProductSubsidyRule($this->fund_provider)
        ] : []);
    }

    /**
     * @return array[]
     */
    private function disabledProductsRules(): array {
        return [
            'disable_products' => 'nullable|array',
            'disable_products.*' => ['required', 'numeric', Rule::exists('products', 'id')->where(
                'organization_id', $this->fund_provider->organization_id
            )],
        ];
    }

    /**
     * @return array
     */
    public function attributes(): array
    {
        return [
            'enable_products.*.amount' => trans('validation.attributes.amount'),
            'enable_products.*.limit_total' => trans('validation.attributes.limit_total'),
            'enable_products.*.limit_per_identity' => trans('validation.attributes.limit_per_identity'),
        ];
    }
}
