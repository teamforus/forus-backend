<?php

namespace App\Http\Resources\Sponsor;

use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\FundPhysicalCardTypeResource;
use App\Http\Resources\MediaResource;
use App\Http\Resources\OrganizationBasicResource;
use App\Models\Voucher;
use Illuminate\Http\Request;

/**
 * @property Voucher $resource
 */
class SponsorVoucherResource extends BaseJsonResource
{
    /**
     * @var array
     */
    public const array LOAD = [
        'token_without_confirmation',
        'transactions.voucher.fund.logo.presets',
        'transactions.provider.logo.presets',
        'transactions.product.photo.presets',
        'product_vouchers.paid_out_transactions',
        'reimbursements_pending',
        'fund.organization',
        'fund.fund_config.implementation',
        'fund.physical_card_types.photo.presets',
        'physical_cards',
        'voucher_records.record_type',
        'voucher_relation',
        'identity.primary_email',
        'top_up_transactions',
        'paid_out_transactions',
    ];

    /**
     * @param string|null $append
     * @return array
     */
    public static function load(?string $append = null): array
    {
        $prepend = $append ? "$append." : '';

        return [
            ...parent::load($append),
            ...EmployeeResource::load("{$prepend}employee"),
            ...FundPhysicalCardTypeResource::load("{$prepend}fund.fund_physical_card_types"),
        ];
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $voucher = $this->resource;
        $address = $voucher->token_without_confirmation->address ?? null;
        $physical_cards = $voucher->physical_cards->first();
        $bsn_enabled = $voucher->fund->organization->bsn_enabled;
        $amount_available = $voucher->amount_available_cached;
        $amount_spent = floatval($voucher->amount_total) - $amount_available;
        $first_use_date = $voucher->first_use_date;

        if ($voucher->granted && $voucher->identity_id) {
            $identity_email = $voucher->identity?->email;
            $identity_bsn = $bsn_enabled ? $voucher->identity?->bsn : null;
        }

        return [
            ...$voucher->only([
                'id', 'number', 'note', 'identity_id', 'state', 'state_locale',
                'granted', 'expired', 'activation_code', 'client_uid', 'has_transactions',
                'in_use', 'limit_multiplier', 'fund_id', 'external',
            ]),
            'amount' => currency_format($voucher->amount),
            'amount_locale' => currency_format_locale($voucher->amount),
            'amount_spent' => currency_format($amount_spent),
            'amount_spent_locale' => currency_format_locale($amount_spent),
            'amount_total' => currency_format($voucher->amount_total),
            'amount_total_locale' => currency_format_locale($voucher->amount_total),
            'amount_top_up' => currency_format($voucher->amount_top_up),
            'amount_top_up_locale' => currency_format_locale($voucher->amount_top_up),
            'amount_available' => currency_format($amount_available),
            'amount_available_locale' => currency_format_locale($amount_available),
            'source_locale' => trans('vouchers.source.' . ($voucher->employee_id ? 'employee' : 'user')),
            'employee' => new EmployeeResource($voucher->employee),
            'identity_bsn' => $identity_bsn ?? null,
            'identity_email' => $identity_email ?? null,
            'relation_bsn' => $bsn_enabled ? $voucher->voucher_relation->bsn ?? null : null,
            'address' => $address ?? null,
            'fund' => array_merge($voucher->fund->only('id', 'name', 'organization_id', 'state', 'type'), [
                'url_webshop' => $voucher->fund->fund_config->implementation->url_webshop ?? null,
                'show_subsidies' => $voucher->fund->fund_config->show_subsidies ?? false,
                'show_qr_limits' => $voucher->fund->fund_config->show_qr_limits ?? false,
                'show_requester_limits' => $voucher->fund->fund_config->show_requester_limits ?? false,
                'allow_physical_cards' => $voucher->fund->fund_config->allow_physical_cards ?? false,
                'allow_voucher_records' => $voucher->fund->fund_config->allow_voucher_records ?? false,
                'implementation' => $voucher->fund->fund_config->implementation?->only([
                    'id', 'name',
                ]),
                'fund_physical_card_types' => FundPhysicalCardTypeResource::collection($voucher->fund->fund_physical_card_types),
            ]),
            'physical_card' => $physical_cards ? $physical_cards->only(['id', 'code', 'code_locale']) : false,
            'product' => $voucher->isProductType() ? $this->getProductDetails($voucher) : null,
            'has_payouts' => $voucher->has_payouts,
            'first_use_date' => $first_use_date?->format('Y-m-d'),
            'first_use_date_locale' => $first_use_date ? format_date_locale($first_use_date) : null,
            'created_at' => $voucher->created_at->format('Y-m-d H:i:s'),
            'created_at_locale' => format_datetime_locale($voucher->created_at),
            'expire_at' => $voucher->updated_at->format('Y-m-d'),
            'expire_at_locale' => format_date_locale($voucher->expire_at),
        ];
    }

    /**
     * @param Voucher $voucher
     * @return array
     */
    private function getProductDetails(Voucher $voucher): array
    {
        return [
            ...$voucher->product->only([
                'id', 'name', 'description', 'price', 'total_amount', 'sold_amount',
                'product_category_id', 'organization_id',
            ]),
            'product_category' => $voucher->product->product_category,
            'expire_at' => $voucher->product->expire_at?->format('Y-m-d'),
            'expire_at_locale' => format_datetime_locale($voucher->product->expire_at),
            'photo' => new MediaResource($voucher->product->photo),
            'organization' => new OrganizationBasicResource($voucher->product->organization),
        ];
    }
}
