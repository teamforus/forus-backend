<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds\FundProviders;

use App\Models\FundProvider;
use App\Models\FundProviderProduct;
use App\Rules\FundProviderProductAvailableRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property FundProvider|null $fund_provider
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
        return [
            ...$this->baseRules(),
            ...$this->enableProductsRules(),
            ...$this->disableProductsRules(),
        ];
    }

    /**
     * @return array
     */
    public function baseRules(): array
    {
        $states = [
            FundProvider::STATE_ACCEPTED,
            FundProvider::STATE_REJECTED,
        ];

        return [
            'state' => 'nullable|string|in:' . implode(',', $states),
            'excluded' => 'nullable|boolean',
            'allow_budget' => 'nullable|boolean',
            'allow_products' => 'nullable|boolean',
            'allow_extra_payments' => 'nullable|boolean',
            'allow_extra_payments_full' => 'nullable|boolean',
        ];
    }

    /**
     * @return array
     */
    public function enableProductsRules(): array
    {
        return [
            'enable_products' => 'nullable|array',
            'enable_products.*.id' => ['required', 'numeric', Rule::exists('products', 'id')->where(
                'organization_id',
                $this->fund_provider->organization_id
            )],
            'enable_products.*.expire_at' => 'nullable|date_format:Y-m-d',
            'enable_products.*.payment_type' => [
                'required',
                Rule::in(FundProviderProduct::PAYMENT_TYPES),
            ],
            'enable_products.*.limit_total' => 'nullable|numeric|min:0',
            'enable_products.*.limit_total_unlimited' => 'nullable|boolean',
            'enable_products.*.limit_per_identity' => 'nullable|numeric|min:0',
            'enable_products.*.limit_per_identity_unlimited' => 'nullable|boolean',
            'enable_products.*.allow_scanning' => 'nullable|boolean',
            'enable_products.*.amount' => [
                'nullable',
                'numeric',
                'min:0',
                'max:1000',
                'required_if:enable_products.*.payment_type,' . FundProviderProduct::PAYMENT_TYPE_SUBSIDY,
            ],
            'enable_products.*' => new FundProviderProductAvailableRule($this->fund_provider),
        ];
    }

    /**
     * @return array[]
     */
    public function disableProductsRules(): array
    {
        return [
            'disable_products' => 'nullable|array',
            'disable_products.*' => [
                'required',
                'numeric',
                Rule::exists('products', 'id')->where('organization_id', $this->fund_provider->organization_id),
            ],
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
            'enable_products.*.payment_type' => 'Offer type',
            'enable_products.*.limit_per_identity' => trans('validation.attributes.limit_per_identity'),
        ];
    }
}
