<?php

namespace App\Http\Resources;

use App\Models\Voucher;

/**
 * Class VoucherResource
 * @property Voucher $resource
 * @package App\Http\Resources
 */
class VoucherCollectionResource extends VoucherResource
{
    /**
     * @var array
     */
    public static $load = [
        'parent',
        'token_with_confirmation',
        'token_without_confirmation',
        'last_transaction',
        'transactions',
        'product_vouchers.fund',
        'product_vouchers.product.photo.presets',
        'product_vouchers.token_with_confirmation',
        'product_vouchers.token_without_confirmation',
        'product.photo.presets',
        'product.product_category.translations',
        'product.organization.logo.presets',
        'product.organization.business_type.translations',
        'fund.fund_config.implementation',
        'fund.logo.presets',
        'fund.organization.logo.presets',
        'fund.organization.business_type.translations',
        'physical_cards',
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
