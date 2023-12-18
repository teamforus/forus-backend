<?php

namespace App\Http\Resources\Sponsor;

use App\Http\Resources\EmployeeResource;
use App\Http\Resources\ReimbursementCategoryResource;
use App\Http\Resources\ReimbursementResource;
use App\Models\ReimbursementCategory;

class SponsorReimbursementResource extends ReimbursementResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $reimbursement = $this->resource;
        $bsn_enabled = $this->resource->voucher->fund->organization->bsn_enabled;

        return array_merge(parent::toArray($request), [
            'identity_email' => $reimbursement->voucher->identity->email,
            'identity_bsn' => $bsn_enabled ? $reimbursement->voucher->identity->bsn : null,
            'provider_name' => $reimbursement->provider_name,
            'employee' => EmployeeResource::create($reimbursement->employee),
            'reimbursement_category' => ReimbursementCategoryResource::create($reimbursement->reimbursement_category),
            'implementation_name' => $reimbursement->voucher->fund->fund_config?->implementation?->name,
        ]);
    }
}
