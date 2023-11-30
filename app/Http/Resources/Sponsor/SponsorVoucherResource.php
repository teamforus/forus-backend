<?php

namespace App\Http\Resources\Sponsor;

use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\MediaResource;
use App\Http\Resources\OrganizationBasicResource;
use App\Models\Voucher;

/**
 * @property Voucher $resource
 */
class SponsorVoucherResource extends BaseJsonResource
{
    /**
     * @var array
     */
    public const LOAD = [
        'token_without_confirmation',
        'transactions.voucher.fund.logo.presets',
        'transactions.provider.logo.presets',
        'transactions.product.photo.presets',
        'product_vouchers',
        'reimbursements_pending',
        'fund.fund_config',
        'fund.organization',
        'physical_cards',
        'voucher_records.record_type',
        'voucher_relation',
        'identity.primary_email',
        'top_up_transactions',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $voucher = $this->resource;
        $address = $voucher->token_without_confirmation->address ?? null;
        $physical_cards = $voucher->physical_cards->first();
        $bsn_enabled = $voucher->fund->organization->bsn_enabled;
        $amount_available = $voucher->fund->isTypeBudget() ? $voucher->amount_available_cached : 0;
        $first_use_date = $voucher->first_use_date;

        if ($voucher->is_granted && $voucher->identity_address) {
            $identity_email = $voucher->identity?->email;
            $identity_bsn = $bsn_enabled ? $voucher->identity?->bsn: null;
        }

        return array_merge($voucher->only([
            'id', 'amount', 'amount_total', 'amount_top_up', 'note', 'identity_address', 'state', 'state_locale',
            'is_granted', 'expired', 'activation_code', 'client_uid', 'has_transactions',
            'in_use', 'limit_multiplier', 'fund_id', 'is_external',
        ]), [
            'amount_available' => currency_format($amount_available),
            'source_locale' => trans('vouchers.source.' . ($voucher->employee_id ? 'employee' : 'user')),
            'identity_bsn' => $identity_bsn ?? null,
            'identity_email' => $identity_email ?? null,
            'relation_bsn' => $bsn_enabled ? $voucher->voucher_relation->bsn ?? null : null,
            'address' => $address ?? null,
            'fund' => array_merge($voucher->fund->only('id', 'name', 'organization_id', 'state', 'type'), [
                'allow_physical_cards' => $voucher->fund->fund_config->allow_physical_cards ?? false,
                'allow_voucher_records' => $voucher->fund->fund_config->allow_voucher_records ?? false,
            ]),
            'physical_card' => $physical_cards ? $physical_cards->only(['id', 'code']) : false,
            'product' => $voucher->isProductType() ? $this->getProductDetails($voucher) : null,
            'first_use_date' => $first_use_date?->format('Y-m-d'),
            'first_use_date_locale' => $first_use_date ? format_date_locale($first_use_date) : null,
            'created_at' => $voucher->created_at->format('Y-m-d H:i:s'),
            'created_at_locale' => format_datetime_locale($voucher->created_at),
            'expire_at' => $voucher->updated_at->format('Y-m-d'),
            'expire_at_locale' => format_date_locale($voucher->expire_at),
        ]);
    }

    /**
     * @param Voucher $voucher
     * @return array
     */
    private function getProductDetails(Voucher $voucher): array
    {
        return array_merge($voucher->product->only([
            'id', 'name', 'description', 'price', 'total_amount', 'sold_amount',
            'product_category_id', 'organization_id',
        ]), [
            'product_category' => $voucher->product->product_category,
            'expire_at' => $voucher->product->expire_at?->format('Y-m-d'),
            'expire_at_locale' => format_datetime_locale($voucher->product->expire_at),
            'photo' => new MediaResource($voucher->product->photo),
            'organization' => new OrganizationBasicResource($voucher->product->organization),
        ]);
    }
}
