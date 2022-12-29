<?php

namespace App\Http\Resources\Sponsor;

use App\Http\Resources\EmployeeResource;
use App\Http\Resources\ReimbursementResource;

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
            'employee' => EmployeeResource::create($reimbursement->employee),
        ]);
    }
}