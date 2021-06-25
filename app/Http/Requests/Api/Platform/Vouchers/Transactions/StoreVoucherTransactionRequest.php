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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
// use Illuminate\Validation\Rule;

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
        return Gate::allows('useAsProvider', $this->voucher_address_or_physical_code->voucher);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $voucher = $this->voucher_address_or_physical_code->voucher;

        if ($voucher->fund->isTypeSubsidy()) {
            $rules = $this->subsidyVoucherRules($voucher);
        } else if ($voucher->fund->isTypeBudget() && $voucher->isBudgetType()) {
            $rules = $this->budgetVoucherRules($voucher);
        }

        return array_merge($rules ?? [], $this->commonRules());
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
            'amount' => [
                'required',
                'numeric',
                'min:.02',
                'max:' . number_format($voucher->amount_available, 2, '.', ''),
            ],
            'organization_id' => [
                'required',
                'exists:organizations,id',
                'in:' . $this->getValidOrganizations($voucher)->implode(',')
            ],
        ];
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
     * @return Collection
     */
    private function getValidOrganizations(Voucher $voucher): Collection
    {
        return OrganizationQuery::whereHasPermissionToScanVoucher(
            Organization::query(),
            $this->auth_address(),
            $voucher
        )->pluck('organizations.id');
    }

    /**
     * @param $voucher
     * @return array
     */
    private function getAvailableSubsidyProductIds($voucher): array
    {
        $query = Product::whereHas('fund_provider_products', function(Builder $builder) use ($voucher) {
            // $organizations = Organization::queryByIdentityPermissions($this->auth_address(), 'scan_vouchers');
            $organizations = $this->getValidOrganizations($voucher);

            FundProviderProductQuery::whereAvailableForSubsidyVoucherFilter(
                $builder,
                $voucher,
                $organizations->toArray()
            );
        });

        return ProductQuery::inStockAndActiveFilter($query)->pluck('id')->toArray();
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
