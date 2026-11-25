<?php

namespace App\Services\MeAppApiService\Http\Resources\Requester;

use App\Models\Enums\VoucherType;
use App\Models\Voucher;
use App\Services\MeAppApiService\Http\Resources\MobileBaseJsonResource;
use Exception;
use Illuminate\Http\Request;

/**
 * @property-read Voucher $resource
 */
class VoucherResource extends MobileBaseJsonResource
{
    /**
     * @var array
     */
    public const array LOAD = [
        'token_with_confirmation',
        'last_transaction',
        'transactions',
        'reimbursements_pending',
        'top_up_transactions',
    ];

    public const array LOAD_NESTED = [
        'fund' => VoucherFundResource::class,
        'product' => VoucherProductResource::class,
        'product_vouchers' => VoucherProductVoucherResource::class,
        'product.organization.offices' => VoucherOfficeResource::class,
        'fund.provider_organizations_approved.offices' => VoucherOfficeResource::class,
        'all_transactions' => VoucherTransactionResource::class,
    ];

    public const array LOAD_COUNT = [
        'transactions',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @throws Exception
     * @return array
     */
    public function toArray(Request $request): array
    {
        $voucher = $this->resource;

        return [
            ...$voucher->only([
                'id', 'expired', 'deactivated', 'external',
            ]),
            'address' => $voucher->token_with_confirmation->address,
            'type' => VoucherType::forVoucher($voucher),
            ...$this->getBaseFields($voucher),
            ...$this->getOptionalFields($voucher),
            'fund' => new VoucherFundResource($voucher->fund),
            'product_vouchers' => VoucherProductVoucherResource::collection($voucher->product_vouchers),
            ...$this->makeTimestamps([
                'last_active_day' => $voucher->last_active_day,
                'last_transaction_at' => $voucher->last_transaction?->created_at,
            ], dateOnly: true),
            ...$this->makeTimestamps($voucher->only([
                'expire_at',
            ])),
        ];
    }

    /**
     * @param Voucher $voucher
     * @return array
     */
    protected function getBaseFields(Voucher $voucher): array
    {
        if ($voucher->isBudgetType()) {
            $amount = $voucher->amount_available_cached;

            return [
                'used' => $amount == 0,
                'product' => null,
                ...($voucher->fund->fund_config->hide_voucher_amount ? [
                    'amount' => null,
                    'amount_locale' => null,
                ] : [
                    'amount' => currency_format($amount),
                    'amount_locale' => currency_format_locale($amount),
                ]),
            ];
        }

        return [
            'used' => $voucher->transactions_count > 0,
            ...($voucher->fund->fund_config->hide_voucher_amount ? [
                'amount' => null,
                'amount_locale' => null,
            ] : [
                'amount' => currency_format($voucher->amount),
                'amount_locale' => currency_format_locale($voucher->amount),
            ]),
            'product' => new VoucherProductResource($voucher->product),
        ];
    }

    /**
     * @param Voucher $voucher
     * @return array
     */
    protected function getOptionalFields(Voucher $voucher): array
    {
        $offices = $voucher->isBudgetType() ?
            $voucher->fund->provider_organizations_approved->pluck('offices')->flatten() :
            $voucher->product->organization->offices;

        return [
            'offices' => VoucherOfficeResource::collection($offices),
            'transactions' => VoucherTransactionResource::collection($voucher->all_transactions),
        ];
    }
}
