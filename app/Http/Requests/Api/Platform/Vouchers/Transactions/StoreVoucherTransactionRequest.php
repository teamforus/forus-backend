<?php

namespace App\Http\Requests\Api\Platform\Vouchers\Transactions;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Voucher;
use App\Models\VoucherToken;
use App\Scopes\Builders\FundProviderProductQuery;
use App\Scopes\Builders\OrganizationQuery;
use App\Scopes\Builders\ProductQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\UnauthorizedException;

/**
 * Class StoreVoucherTransactionRequest
 * @property VoucherToken $voucher_address_or_physical_code
 * @package App\Http\Requests\Api\Platform\Vouchers\Transactions
 */
class StoreVoucherTransactionRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        $voucher = $this->voucher_address_or_physical_code->voucher;

        if ($voucher->fund->isTypeBudget() && $voucher->isBudgetType() && $this->has('product_id')) {
            return Gate::allows('useAsProviderWithProducts', [
                $voucher,
                $this->input('product_id'),
            ]);
        }

        return Gate::allows('useAsProvider', $voucher);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $voucher = $this->voucher_address_or_physical_code->voucher;
        $commonRules = $this->commonRules();

        if ($voucher->fund->isTypeSubsidy()) {
            return array_merge($this->subsidyVoucherRules($voucher), $commonRules);
        }

        if ($voucher->fund->isTypeBudget() && $voucher->isBudgetType()) {
            return array_merge($this->budgetVoucherRules($voucher), $commonRules);
        }

        if ($voucher->fund->isTypeBudget() && $voucher->isProductType()) {
            return $commonRules;
        }

        throw new UnauthorizedException('Invalid voucher type.');
    }

    /**
     * @return string[]
     */
    private function commonRules(): array
    {
        return [
            'note' => 'nullable|string|between:2,255',
        ];
    }

    /**
     * @param Voucher $voucher
     * @return \string[][]
     */
    private function budgetVoucherRules(Voucher $voucher): array
    {
        return [
            'product_id' => [
                $this->has('amount') ? 'nullable' :  'required',
                'in:' . implode(',', $this->getValidBudgetProducts($voucher))
            ],
            'amount' => [
                $this->has('product_id') ? 'nullable' :  'required',
                'numeric',
                'min:.02',
                'max:' . number_format($voucher->amount_available, 2, '.', ''),
            ],
            'organization_id' => [
                $this->has('amount') ? 'required' :  'nullable',
                'exists:organizations,id',
                'in:' . $this->getValidOrganizations($voucher)->pluck('id')->implode(',')
            ],
        ];
    }

    /**
     * @param Voucher $voucher
     * @return array
     */
    private function getValidBudgetProducts(Voucher $voucher): array
    {
        return $this->has('organization_id') ? ProductQuery::whereAvailableForVoucher(
            Product::query(),
            $voucher,
            Organization::whereId($this->input('organization_id'))->select('id'),
            false
        )->pluck('products.id')->toArray() : [];
    }

    /**
     * @param Voucher $voucher
     * @return \string[][]
     */
    private function subsidyVoucherRules(Voucher $voucher): array
    {
        $availableProducts = $this->getAvailableSubsidyProductIds($voucher);
        $products = $voucher->product_id ? array_intersect($availableProducts, [
            $voucher->product_id
        ]): $availableProducts;

        return [
            'product_id' => 'required|exists:products,id|in:' . join(',', $products),
        ];
    }

    /**
     * @param Voucher $voucher
     * @return Builder
     */
    private function getValidOrganizations(Voucher $voucher): Builder
    {
        if (!$this->has('product_id') && !$this->has('amount')) {
            return Organization::where('id', '<', -999);
        }

        return Organization::query()->where(function(Builder $builder) use ($voucher) {
            $builder->where(function(Builder $builder) use ($voucher) {
                if ($this->has('product_id')) {
                    if ($voucher->fund->isTypeSubsidy()) {
                        $builder->whereHas('fund_providers', function(Builder $builder) use ($voucher) {
                            $builder->whereHas('fund_provider_products', function(Builder $builder) use ($voucher) {
                                FundProviderProductQuery::whereAvailableForSubsidyVoucher($builder, $voucher);
                            });
                        });
                    } else {
                        // Product approved to be bought by target voucher
                        $builder->whereHas('products', function(Builder $builder) use ($voucher) {
                            ProductQuery::whereAvailableForVoucher($builder, $voucher, null, false);
                        });
                    }
                }

                if ($this->has('amount')) {
                    OrganizationQuery::whereHasPermissionToScanVoucher(
                        $builder,
                        $this->auth_address(),
                        $voucher
                    );
                }
            });
        });
    }

    /**
     * @param $voucher
     * @return array
     */
    private function getAvailableSubsidyProductIds($voucher): array
    {
        return Product::whereHas('fund_provider_products', function(Builder $builder) use ($voucher) {
            $organizations = $this->getValidOrganizations($voucher);

            FundProviderProductQuery::whereAvailableForSubsidyVoucher(
                $builder,
                $voucher,
                $organizations->select('id')
            );
        })->pluck('id')->toArray();
    }

    /**
     * @return array
     */
    public function messages(): array
    {
        return [
            'amount.max' => trans('validation.voucher.not_enough_funds')
        ];
    }
}
