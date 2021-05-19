<?php

namespace App\Http\Resources;

use App\Models\Voucher;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
        'transactions:id,amount,voucher_id',
        'last_transaction',
        'product_vouchers.fund',
        'product_vouchers.product.photo.presets',
        'product_vouchers.token_with_confirmation',
        'product_vouchers.token_without_confirmation',
        'product.photo.presets',
        'product.product_category.translations',
        'product.organization.logo.presets',
        'fund.fund_config.implementation',
        'fund.logo.presets',
        'fund.organization.logo.presets',
        'physical_cards',
    ];

    /**
     * @return array|string[]
     */
    public static function load(): array
    {
        $load = static::$load;

        if (!env('REMOVE_VOUCHERS_LIST_TRANSACTIONS_SOFT', FALSE)) {
            $load = array_merge($load, [
                'transactions.voucher.fund.logo.presets',
                'transactions.provider.logo.presets',
                'transactions.product.photo.presets',
            ]);
        }

        if (!env('REMOVE_VOUCHERS_LIST_OFFICES_SOFT', FALSE)) {
            $load = array_merge($load, [
                'product.organization.offices.schedules',
                'product.organization.offices.photo.presets',
                'product.organization.offices.organization.logo.presets',
                'fund.provider_organizations_approved.offices.schedules',
                'fund.provider_organizations_approved.offices.photo.presets',
                'fund.provider_organizations_approved.offices.organization.logo.presets',
            ]);
        }

        return $load;
    }

    /**
     * @param any|\Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $data = parent::toArray($request);

        if (env('REMOVE_VOUCHERS_LIST_TRANSACTIONS_HARD', FALSE)) {
            unset($data['transactions']);
        }

        if (env('REMOVE_VOUCHERS_LIST_OFFICES_HARD', FALSE)) {
            unset($data['offices']);
        }

        return $data;
    }

    /**
     * @param Voucher $voucher
     * @return AnonymousResourceCollection
     */
    protected function getTransactions(Voucher $voucher): AnonymousResourceCollection
    {
        if (env('REMOVE_VOUCHERS_LIST_TRANSACTIONS_SOFT', FALSE)) {
            return VoucherTransactionResource::collection([]);
        }

        return parent::getTransactions($voucher);
    }

    /**
     * @param Voucher $voucher
     * @return AnonymousResourceCollection
     */
    protected function getOffices(Voucher $voucher): AnonymousResourceCollection
    {
        if (env('REMOVE_VOUCHERS_LIST_OFFICES_SOFT', FALSE)) {
            return OfficeResource::collection([]);
        }

        return parent::getOffices($voucher);
    }
}
