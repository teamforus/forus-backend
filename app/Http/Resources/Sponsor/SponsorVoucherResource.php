<?php

namespace App\Http\Resources\Sponsor;

use App\Http\Resources\MediaResource;
use App\Http\Resources\OrganizationBasicResource;
use App\Models\Voucher;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class SponsorVoucherResource
 * @property Voucher $resource
 * @package App\Http\Resources\Sponsor
 */
class SponsorVoucherResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $recordRepo = resolve('forus.services.record');
        $voucher = $this->resource;
        $address = $voucher->token_without_confirmation->address ?? null;
        $physical_cards = $voucher->physical_cards()->first();
        $bsn_enabled = $voucher->fund->organization->bsn_enabled;

        if ($voucher->is_granted && $voucher->identity_address) {
            $identity_email = $recordRepo->primaryEmailByAddress($voucher->identity_address);
            $identity_bsn = $bsn_enabled ? $recordRepo->bsnByAddress($voucher->identity_address): null;
        }

        return array_merge($voucher->only([
            'id', 'amount', 'note', 'identity_address', 'state', 'state_locale', 'is_granted',
            'expired', 'activation_code', 'activation_code_uid', 'has_transactions',
            'in_use', 'limit_multiplier',
        ]), [
            'history' => $this->getHistory($voucher),
            'source' => $voucher->employee_id ? 'employee' : 'user',
            'identity_bsn' => $identity_bsn ?? null,
            'identity_email' => $identity_email ?? null,
            'relation_bsn' => $bsn_enabled ? $voucher->voucher_relation->bsn ?? null : null,
            'address' => $address ?? null,
            'fund' => array_merge($voucher->fund->only('id', 'name', 'organization_id', 'state', 'type'), [
                'allow_physical_cards' => $voucher->fund->fund_config->allow_physical_cards ?? false,
            ]),
            'physical_card' => $physical_cards ? $physical_cards->only(['id', 'code']) : false,
            'product' => $voucher->isProductType() ? $this->getProductDetails($voucher) : null,
            'created_at' => $voucher->created_at->format('Y-m-d H:i:s'),
            'expire_at' => $voucher->updated_at->format('Y-m-d'),
            'created_at_locale' => format_datetime_locale($voucher->created_at),
            'expire_at_locale' => format_date_locale($voucher->expire_at),
        ]);
    }

    /**
     * @param Voucher $voucher
     * @return array
     */
    private function getProductDetails(Voucher $voucher): array {
        return array_merge($voucher->product->only([
            'id', 'name', 'description', 'price', 'total_amount', 'sold_amount',
            'product_category_id', 'organization_id'
        ]), [
            'product_category' => $voucher->product->product_category,
            'expire_at' => $voucher->product->expire_at ? $voucher->product->expire_at->format('Y-m-d') : null,
            'expire_at_locale' => format_datetime_locale($voucher->product->expire_at),
            'photo' => new MediaResource($voucher->product->photo),
            'organization' => new OrganizationBasicResource($voucher->product->organization),
        ]);
    }

    /**
     * @return mixed
     */
    public function getHistory(Voucher $voucher): array
    {
        return $voucher->sponsorHistoryLogs()->map(function (EventLog $log) {
            $employee_id = $log->data['employee_id'] ?? null;
            $employee_email = $employee_id ? $log->data['employee_email'] ?: null : null;

            return array_merge($log->only('id', 'event', 'event_locale'), [
                'employee_id' => $employee_id,
                'employee_email' => $employee_email,
                'note' => $log->data['note'] ?? '',
                'created_at' => $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : null,
                'created_at_locale' => format_datetime_locale($log->created_at),
                'updated_at' => $log->updated_at ? $log->updated_at->format('Y-m-d H:i:s') : null,
                'updated_at_locale' => format_date_locale($log->updated_at),
            ]);
        })->values()->toArray();
    }
}
