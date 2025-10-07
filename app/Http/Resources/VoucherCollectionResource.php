<?php

namespace App\Http\Resources;

use App\Models\Voucher;

/**
 * @property Voucher $resource
 */
class VoucherCollectionResource extends VoucherResource
{
    /**
     * @var array
     */
    public static array $load = [
        'logs',
        'parent',
        'token_with_confirmation',
        'token_without_confirmation',
        'last_transaction',
        'transactions',
        'product_vouchers.fund',
        'product_vouchers.product.photos.presets',
        'product_vouchers.token_with_confirmation',
        'product_vouchers.token_without_confirmation',
        'product.photos.presets',
        'product.product_category.translations',
        'product.organization.logo.presets',
        'product.organization.business_type.translations',
        'fund.fund_config.implementation',
        'fund.logo.presets',
        'fund.organization.logo.presets',
        'fund.organization.business_type.translations',
        'physical_cards',
        'last_deactivation_log',
    ];

    /**
     * @param Voucher $voucher
     * @return array
     */
    protected function getOptionalFields(Voucher $voucher): array
    {
        return [];
    }
}
