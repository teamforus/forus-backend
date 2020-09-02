<?php

namespace App\Http\Requests\Api\Platform\Vouchers\Transactions;

use App\Models\Organization;
use App\Models\Voucher;
use App\Models\VoucherToken;
use App\Scopes\Builders\OrganizationQuery;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class StoreVoucherTransactionRequest
 * @property VoucherToken $voucher_address_or_physical_code
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
        $voucher = $this->voucher_address_or_physical_code->voucher;

        $validOrganizations = OrganizationQuery::whereHasPermissionToScanVoucher(
            Organization::query(),
            auth_address(),
            $voucher
        )->pluck('organizations.id');

        return array_merge($voucher->type === Voucher::TYPE_BUDGET ? [
            'amount' => [
                'required_without:product_id',
                'numeric',
                'min:.02',
                'max:' . number_format($voucher->amount_available, 2, '.', ''),
            ],
            'organization_id' => [
                'required',
                'exists:organizations,id',
                'in:' . $validOrganizations->implode(',')
            ],
        ] : [], [
            'note' => 'nullable|string|between:2,255',
        ]);
    }

    public function messages()
    {
        return [
            'amount.max' => trans('validation.voucher.not_enough_funds')
        ];
    }
}
