<?php

namespace App\Http\Requests\Api\Platform\Vouchers\Transactions;

use App\Models\Organization;
use App\Models\Product;
use App\Models\Voucher;
use App\Models\VoucherToken;
use App\Scopes\Builders\OrganizationQuery;
use App\Scopes\Builders\ProductQuery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * Class StoreVoucherTransactionRequest
 * @property VoucherToken $voucher_token_address
 * @package App\Http\Requests\Api\Platform\Vouchers\Transactions
 */
class StoreVoucherTransactionRequest extends FormRequest
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
        // target voucher
        $voucher = $this->voucher_token_address->voucher;
        $validOrganizations = $this->getValidOrganizations($voucher);

        $rules = [
            'note' => 'nullable|string|between:2,255',
        ];

        if ($voucher->type === $voucher::TYPE_BUDGET) {
            $rules = array_merge($rules, [
                'organization_id' => [
                    'required',
                    'exists:organizations,id',
                    'in:' . $validOrganizations->implode(',')
                ],
            ]);
        }

        if ($voucher->type === $voucher::TYPE_BUDGET && !$voucher->fund->isTypeSubsidy()) {
            $rules = array_merge($rules, [
                'amount' => [
                    'required',
                    'numeric',
                    'min:.02',
                    'max:' . number_format($voucher->amount_available, 2, '.', ''),
                ],
            ]);
        }

        if ($voucher->fund->isTypeSubsidy()) {
            $rules = array_merge($rules, [
                'product_id' => [
                    'required',
                    'exists:products,id',
                    Rule::in(ProductQuery::approvedForFundsAndActiveFilter(
                        Product::query(), $voucher->fund_id
                    )->where([
                        'products.organization_id' => $this->input('organization_id')
                    ])->pluck('products.id')->toArray()),
                ],
            ]);
        }

        return $rules;
    }

    private function getValidOrganizations(Voucher $voucher): Collection {
        return OrganizationQuery::whereHasPermissionToScanVoucher(
            Organization::query(),
            auth_address(),
            $voucher
        )->pluck('organizations.id');
    }

    public function messages(): array
    {
        return [
            'amount.max' => trans('validation.voucher.not_enough_funds')
        ];
    }
}
