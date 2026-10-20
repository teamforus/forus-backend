<?php

namespace App\Services\MeAppApiService\Http\Resources\Requester;

use App\Http\Resources\BaseJsonResource;
use App\Models\VoucherTransaction;
use App\Services\MeAppApiService\Http\Resources\Shared\MediaResource;
use Illuminate\Http\Request;

/**
 * @property-read VoucherTransaction $resource
 */
class VoucherTransactionResource extends BaseJsonResource
{
    /**
     * @var array
     */
    public const array LOAD = [
        'product.organization', 'voucher.fund', 'reimbursement',
    ];

    public const array LOAD_NESTED = [
        'provider.logo' => MediaResource::class,
        'product.photos' => MediaResource::class,
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
        $product = $transaction->product;

        return [
            ...$transaction->only([
                'id', 'organization_id', 'product_id', 'address', 'state', 'state_locale', 'target',
            ]),
            'amount' => currency_format($transaction->amount),
            'amount_locale' => currency_format_locale($transaction->amount),
            'amount_extra_cash' => currency_format($transaction->amount_extra_cash),
            'amount_extra_cash_locale' => currency_format_locale($transaction->amount_extra_cash),
            'organization' => $transaction->provider ? [
                ...$transaction->provider->only([
                    'id', 'name',
                ]),
                'logo' => $transaction->provider->logo ? new MediaResource($transaction->provider->logo) : null,
            ] : null,
            'product' => $product ? [
                ...$product->only([
                    'id', 'name', 'organization_id', 'product_category_id', 'description',
                ]),
                ...$product->translateColumns($product->only([
                    'name',
                ])),
                'price' => $product->price === null ? null : currency_format($product->price),
                'organization' => $product->organization->only([
                    'id', 'name',
                ]),
                'photo' => $product->photos->isNotEmpty() ? new MediaResource($product->photos->first()) : null,
            ] : null,
            'fund' => $transaction->voucher->fund->only([
                'id', 'name',
            ]),
            'reimbursement' => $transaction->reimbursement?->only([
                'id', 'code', 'title',
            ]),
            ...$this->makeTimestamps($transaction->only([
                'created_at', 'transfer_at',
            ])),
        ];
    }
}
