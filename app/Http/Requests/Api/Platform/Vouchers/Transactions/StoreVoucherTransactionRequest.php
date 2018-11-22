<?php

namespace App\Http\Requests\Api\Platform\Vouchers\Transactions;

use App\Models\Organization;
use App\Models\Product;
use App\Models\Voucher;
use Illuminate\Foundation\Http\FormRequest;

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
        $voucherOrganizations = $voucher->fund->providers()->where([
            'state' => 'approved'
        ])->pluck('organization_id');

        /**
         * Organization approved by voucher fund
         */
        $validOrganizations = collect($voucherOrganizations->intersect(
            $identityOrganizations
        ));

        /**
         * Product categories approved by fund
         */
        $validCategories = $voucher->fund->product_categories->pluck('id');

        /**
         * Products approved by funds
         */
        $validProductsIds = Organization::getModel()->whereIn(
            'id', $validOrganizations
        )->get()->pluck('products')->flatten()->filter(function($product) use ($validCategories) {
            /** @var Product $product */
            return $validCategories->search($product->product_category_id) !== false;
        })->pluck('id');

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
                    'min:.01',
                    'max:' . $maxAmount,
                ],
                'product_id'        => [
                    'exists:products,id',
                    'in:' . $validProductsIds->implode(',')
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
