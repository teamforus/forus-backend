<?php

namespace App\Http\Resources\Provider;

use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\MediaResource;
use App\Models\VoucherTransaction;
use Illuminate\Http\Request;

/**
 * @property VoucherTransaction $resource
 */
class ProviderVoucherTransactionEmployeeResource extends BaseJsonResource
{
    public const array LOAD = [
        'provider',
        'product',
        'voucher.fund',
        'voucher.fund.organization',
        'fund_provider_product',
        'notes',
    ];

    public const array LOAD_NESTED = [
        'provider.logo' => MediaResource::class,
        'product.photos' => MediaResource::class,
        'voucher.fund.logo' => MediaResource::class,
        'voucher.fund.organization.logo' => MediaResource::class,
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
        $fund_provider_product = $transaction->fund_provider_product;

        $product_price = $fund_provider_product->price ?? null;

        return array_merge($transaction->only([
            'id', 'organization_id', 'product_id', 'address', 'state',
        ]), [
            'note' => $transaction->notes->where('group', 'provider')[0]->message ?? null,
            'created_at' => $transaction->created_at ? $transaction->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $transaction->updated_at ? $transaction->updated_at->format('Y-m-d H:i:s') : null,
            'created_at_locale' => format_datetime_locale($transaction->created_at),
            'updated_at_locale' => format_datetime_locale($transaction->updated_at),
            'amount' => currency_format($transaction->amount),
            'amount_extra_cash' => currency_format($transaction->amount_extra_cash),
            'amount_extra_cash_locale' => currency_format_locale($transaction->amount_extra_cash),
            'product_price' => $product_price ? currency_format($product_price) : null,
            'cancelable' => $transaction->isCancelable(),
            'transfer_in' => $transaction->daysBeforeTransaction(),
            'organization' => array_merge($transaction->provider->only([
                'id', 'name',
            ]), [
                'logo' => new MediaResource($transaction->provider->logo),
            ]),
            'product' => $transaction->product ? array_merge($transaction->product->only([
                'id', 'name', 'organization_id',
            ]), [
                'photos' => MediaResource::collection($transaction->product->photos),
            ]) : null,
            'fund' => array_merge($transaction->voucher->fund->only([
                'id', 'name', 'organization_id',
            ]), [
                'logo' => new MediaResource($transaction->voucher->fund->logo),
                'organization' => array_merge($transaction->voucher->fund->organization->only([
                    'id', 'name',
                ]), [
                    'logo' => new MediaResource($transaction->voucher->fund->organization->logo),
                ]),
            ]),
        ]);
    }
}
