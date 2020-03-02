<?php

namespace App\Http\Requests\Api\Platform\Vouchers\Transactions;

use App\Models\Organization;
use App\Models\Product;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class StoreVoucherTransactionRequest
 * @package App\Http\Requests\Api\Platform\Vouchers\Transactions
 */
class StoreVoucherTransactionRequest extends FormRequest
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
        $product = false;

        /**
         * shopkeeper identity and organizations
         */
        $identityOrganizations = Organization::queryByIdentityPermissions(
            auth()->id(), 'scan_vouchers'
        )->pluck('id');

        /**
         * target voucher
         *
         * @var Voucher $voucher
         */
        $voucher = request()->voucher_token_address->voucher;

        if (!$voucher->product_id && !$this->has('product_id')) {
            $voucherOrganizations = $voucher->fund
                ->provider_organizations_approved_budget()->pluck('organization_id');
        } else {
            $voucherOrganizations = $voucher->fund
                ->provider_organizations_approved_products()->pluck('organization_id');
        }

        /**
         * Organization approved by voucher fund
         */
        $validOrganizations = collect($voucherOrganizations->intersect(
            $identityOrganizations
        ));

        /**
         * Products approved by funds
         */
        $validProductsIds = [];

        if ($this->has('organization_id')) {
            $organization = Organization::find($this->has('organization_id'));

            if ($organization) {
                $validProductsIds = $organization->products();
                $validProductsIds = $validProductsIds->whereHas('fund_providers', function (
                    Builder $builder
                ) use ($voucher) {
                    $builder->where('fund_id', $voucher->fund_id);
                })->orWhereHas('organization.supplied_funds_approved_products')->pluck('products.id');
            }
        }

        if ($voucher->product) {
            $product = $voucher->product;
        } else if (request()->has('product_id')) {
            $product = Product::query()->find(request()->input('product_id'));
        }

        /**
         * If the product is specified,
         * limit available organization also by the product organization
         */
        if ($product) {
            $validOrganizations = $validOrganizations->intersect([
                $product->organization_id
            ]);
        }

        $maxAmount = number_format($voucher->amount_available, 2, '.', '');

        if (!$voucher->product_id) {
            return [
                'note' => 'nullable|string|between:2,255',
                'amount'            => [
                    'required_without:product_id',
                    'numeric',
                    'min:.02',
                    'max:' . $maxAmount,
                ],
                'product_id'        => [
                    Rule::exists('products', 'id'),
                    Rule::in($validProductsIds->toArray())
                ],
                'organization_id'   => [
                    'required',
                    'exists:organizations,id',
                    'in:' . $validOrganizations->implode(',')
                ]
            ];
        }

        return [
            'note' => 'nullable|string|between:2,255',
        ];
    }

    public function messages()
    {
        return [
            'amount.max' => trans('validation.voucher.not_enough_funds')
        ];
    }
}
