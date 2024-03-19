<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds\FundProviders;

use App\Models\FundProvider;
use App\Rules\FundProviderProductAvailableRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property FundProvider|null $fund_provider
 */
class UpdateFundProviderRequest extends FormRequest
{


    /**
     * @return string[]
     *
     * @psalm-return array{state: string, excluded: 'nullable|boolean', allow_budget: 'nullable|boolean', allow_products: 'nullable|boolean', allow_extra_payments: 'nullable|boolean'}
     */
    private function baseRules(): array
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
        ];
    }

    /**
     * @return ((\Illuminate\Validation\Rules\Exists|\Illuminate\Validation\Rules\In|string)[]|string)[]
     *
     * @psalm-return array{reset_products: 'nullable|array', 'reset_products.*.id': list{'required', 'numeric', \Illuminate\Validation\Rules\Exists|\Illuminate\Validation\Rules\In}}
     */
    private function resetProductsRules(): array
    {
        $isBudgetType = $this->fund_provider?->fund?->isTypeBudget();

        $productsRule = $isBudgetType ? Rule::exists('products', 'id')->where(
            'organization_id', $this->fund_provider->organization_id
        ) : Rule::in([]);

        return [
            'reset_products' => 'nullable|array',
            'reset_products.*.id' => ['required', 'numeric', $productsRule],
        ];
    }

    /**
     * @return ((\Illuminate\Validation\Rules\Exists|string)[]|FundProviderProductAvailableRule|string)[]
     *
     * @psalm-return array{enable_products: 'nullable|array', 'enable_products.*.id': list{'required', 'numeric', \Illuminate\Validation\Rules\Exists}, 'enable_products.*.expire_at': 'nullable|date_format:Y-m-d', 'enable_products.*.limit_total': 'nullable|numeric|min:0', 'enable_products.*.limit_total_unlimited': 'nullable|boolean', 'enable_products.*.limit_per_identity': 'nullable|numeric|min:0', 'enable_products.*.amount'?: 'required|numeric|min:0', 'enable_products.*': FundProviderProductAvailableRule}
     */
    private function enableProductsRules(): array
    {
        return array_merge([
            'enable_products' => 'nullable|array',
            'enable_products.*.id' => ['required', 'numeric', Rule::exists('products', 'id')->where(
                'organization_id', $this->fund_provider->organization_id
            )],
            'enable_products.*.expire_at' => 'nullable|date_format:Y-m-d',
            'enable_products.*.limit_total' => 'nullable|numeric|min:0',
            'enable_products.*.limit_total_unlimited' => 'nullable|boolean',
            'enable_products.*.limit_per_identity' => 'nullable|numeric|min:0',
        ], $this->fund_provider->fund->isTypeSubsidy() ? [
            'enable_products.*.amount' => 'required|numeric|min:0',
        ] : [], [
            'enable_products.*' => new FundProviderProductAvailableRule($this->fund_provider)
        ]);
    }

    /**
     * @return ((\Illuminate\Validation\Rules\Exists|string)[]|string)[]
     *
     * @psalm-return array{disable_products: 'nullable|array', 'disable_products.*': list{'required', 'numeric', \Illuminate\Validation\Rules\Exists}}
     */
    private function disableProductsRules(): array
    {
        return [
            'disable_products' => 'nullable|array',
            'disable_products.*' => ['required', 'numeric', Rule::exists('products', 'id')->where(
                'organization_id', $this->fund_provider->organization_id
            )],
        ];
    }

    /**
     * @return (\Illuminate\Contracts\Translation\Translator|array|null|string)[]
     *
     * @psalm-return array{'enable_products.*.amount': \Illuminate\Contracts\Translation\Translator|array|null|string, 'enable_products.*.limit_total': \Illuminate\Contracts\Translation\Translator|array|null|string, 'enable_products.*.limit_per_identity': \Illuminate\Contracts\Translation\Translator|array|null|string}
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
