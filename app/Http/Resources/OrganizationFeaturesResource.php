<?php

namespace App\Http\Resources;

use App\Models\Organization;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Organization $resource
 */
class OrganizationFeaturesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $organization = $this->resource;

        return [
            'statuses' => [
                'bng' => true,
                'digid' => $organization->digidEnabled(),
                'auth_2_fa' => $organization->allow_2fa_restrictions,
                'bi_tools' => $organization->allow_bi_connection,
                'backoffice_api' => $organization->backoffice_available,
                'physical_cards' => $organization->physicalCardsEnabled(),
                'reimbursements' => $organization->reimbursementsEnabled(),
                'voucher_records' => $organization->voucherRecordsEnabled(),
                'haalcentraal_api' => $organization->iConnectApiOinEnabled(),
                'email_connection' => true,
            ]
        ];
    }
}