<?php

namespace App\Services\MeAppApiService\Http\Resources\Provider;

use App\Http\Resources\Small\MediaSmallResource;
use App\Models\Enums\VoucherTransactionState;
use App\Models\VoucherTransaction;
use App\Services\MeAppApiService\Http\Resources\MobileBaseJsonResource;
use Illuminate\Http\Request;

/**
 * @property-read VoucherTransaction $resource
 */
class ProviderVoucherTransactionEmployeeResource extends MobileBaseJsonResource
{
    public const array LOAD = [
        'notes',
        'voucher.fund.organization',
    ];

    public const array LOAD_NESTED = [
        'provider.logo' => MediaSmallResource::class,
        'product.photos' => MediaSmallResource::class,
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $transaction = $this->resource;

        return [
            ...$transaction->only([
                'id', 'organization_id', 'product_id',
            ]),
            'state' => VoucherTransactionState::forTransaction($transaction),
            'note' => $transaction->notes->where('group', 'provider')[0]->message ?? null,
            'amount' => currency_format($transaction->amount),
            'amount_locale' => currency_format_locale($transaction->amount),
            'amount_extra_cash' => currency_format($transaction->amount_extra_cash),
            'amount_extra_cash_locale' => currency_format_locale($transaction->amount_extra_cash),
            'organization' => [
                ...$transaction->provider->only([
                    'id', 'name',
                ]),
                'logo' => $transaction->provider->logo ? new MediaSmallResource($transaction->provider->logo) : null,
            ],
            'product' => $transaction->product ? [
                ...$transaction->product->only([
                    'id', 'name', 'organization_id',
                ]),
                'photos' => MediaSmallResource::collection($transaction->product->photos),
            ] : null,
            'fund' => [
                ...$transaction->voucher->fund->only([
                    'id', 'name', 'organization_id',
                ]),
                'organization' => $transaction->voucher->fund->organization->only([
                    'id', 'name',
                ]),
            ],
            ...$this->makeTimestamps($transaction->only([
                'created_at',
            ])),
        ];
    }
}
