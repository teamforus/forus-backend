<?php

namespace App\Http\Resources\Sponsor;

use App\Http\Resources\MediaResource;
use App\Http\Resources\OrganizationBasicResource;
use App\Models\Voucher;
use App\Services\Forus\Record\Repositories\Interfaces\IRecordRepo;
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

        if ($voucher->is_granted && $voucher->identity_address) {
            $address = $voucher->token_without_confirmation->address ?? null;
            $identity_bsn = $recordRepo->bsnByAddress($voucher->identity_address);
            $identity_email = $recordRepo->primaryEmailByAddress($voucher->identity_address);
        }

        return array_merge($voucher->only([
            'id', 'amount', 'note', 'identity_address',
        ]), [
            'source' => $voucher->employee_id ? 'employee' : 'user',
            'is_granted' => $voucher->is_granted,
            'identity_bsn' => $identity_bsn ?? null,
            'identity_email' => $identity_email ?? null,
            'relation_bsn' => $voucher->voucher_relation->bsn ?? null,
            'has_transactions' => $voucher->has_transactions,
            'address' => $address ?? null,
            'created_at' => $voucher->created_at->format('Y-m-d H:i:s'),
            'expire_at' => $voucher->updated_at->format('Y-m-d'),
            'created_at_locale' => format_datetime_locale($voucher->created_at),
            'expire_at_locale' => format_date_locale($voucher->expire_at),
            'fund' => $voucher->fund->only('id', 'name', 'organization_id', 'state'),
            'product' => $voucher->isProductType() ? $this->getProductDetails($voucher) : null,
        ]);
    }

    /**
     * @param Voucher $voucher
     * @return array
     */
    private function getProductDetails(Voucher $voucher): array {
        return array_merge($voucher->product->only([
            'id', 'name', 'description', 'price', 'old_price',
            'total_amount', 'sold_amount', 'product_category_id',
            'organization_id'
        ]), [
            'product_category' => $voucher->product->product_category,
            'expire_at' => $voucher->product->expire_at->format('Y-m-d'),
            'expire_at_locale' => format_datetime_locale($voucher->product->expire_at),
            'photo' => new MediaResource($voucher->product->photo),
            'organization' => new OrganizationBasicResource($voucher->product->organization),
        ]);
    }
}
