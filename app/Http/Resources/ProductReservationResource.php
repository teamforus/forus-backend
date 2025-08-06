<?php

namespace App\Http\Resources;

use App\Models\Identity;
use App\Models\ProductReservation;
use App\Models\Voucher;
use Illuminate\Http\Request;

/**
 * @property ProductReservation $resource
 */
class ProductReservationResource extends BaseProductReservationResource
{
    public const array LOAD = [
        'voucher.fund.fund_config',
        'voucher.fund.organization',
        'voucher.identity.primary_email',
        'voucher.physical_cards',
        'voucher.voucher_records',
        'product.organization',
        'product.photo.presets',
        'voucher_transaction',
        'extra_payment.refunds',
        'extra_payment.refunds_active',
        'custom_fields.organization_reservation_field',
        'fund_provider_product_with_trashed',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $reservation = $this->resource;
        $voucher = $this->resource->voucher;
        $transaction = $this->resource->voucher_transaction;

        return [
            ...$reservation->only([
                'id', 'state', 'state_locale', 'amount', 'code', 'amount_extra',
                'first_name', 'last_name', 'user_note', 'phone', 'address', 'archived',
                'cancellation_note', 'rejection_note',
            ]),
            'amount_locale' => currency_format_locale($reservation->amount),
            'expired' => $reservation->isExpired(),
            'canceled' => $reservation->isCanceled(),
            'cancelable' => $reservation->isCancelableByRequester(),
            'acceptable' => $reservation->isAcceptable(),
            'rejectable' => $reservation->isCancelableByProvider(),
            'archivable' => $reservation->isArchivable(),
            'product' => $this->productData($reservation),
            'fund' => [
                'id' => $voucher->fund->id,
                ...$voucher->fund->translateColumns($voucher->fund->only('name')),
                'organization' => $voucher->fund->organization->only('id', 'name'),
            ],
            'voucher_transaction' => $transaction?->only('id', 'address'),
            'custom_fields' => ProductReservationFieldValueResource::collection($reservation->custom_fields),
            'records_title' => $voucher->getRecordsTitle(),
            ...$this->getProductPrice($reservation),
            ...$this->identityData($reservation, $voucher),
            ...$this->extraPaymentData($reservation),
            ...$this->makeTimestamps($reservation->only([
                'created_at', 'accepted_at', 'rejected_at', 'canceled_at', 'expire_at', 'birth_date',
            ]), true),
        ];
    }

    /**
     * @param ProductReservation $reservation
     * @param Voucher $voucher
     * @return array
     */
    protected function identityData(ProductReservation $reservation, Voucher $voucher): array
    {
        $physicalCard = $voucher->physical_cards[0] ?? null;

        return $reservation->product->organization->identityCan(Identity::auth(), 'scan_vouchers') ? [
            'identity_email' => $voucher->identity?->email,
            'identity_physical_card' => $physicalCard->code ?? null,
        ] : [];
    }
}
