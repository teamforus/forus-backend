<?php

namespace App\Http\Resources;

use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Organization;

/**
 * @property Organization $resource
 */
class OrganizationFeaturesResource extends BaseJsonResource
{
    public const array LOAD = [
        'implementations',
        'funds.fund_config',
        'funds.organization',
    ];

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
                'digid' => $this->isDigidEnabled($organization),
                'auth_2_fa' => $organization->allow_2fa_restrictions,
                'bi_tools' => $organization->allow_bi_connection,
                'backoffice_api' => $organization->backoffice_available,
                'physical_cards' => $this->isPhysicalCardsEnabled($organization),
                'reimbursements' => $this->isReimbursementsEnabled($organization),
                'voucher_records' => $this->isVoucherRecordsEnabled($organization),
                'iconnect_api' => $this->isIConnectApiOinEnabled($organization),
                'fund_requests' => $this->isFundRequestsEnabled($organization),
                'extra_payments' => $this->isExtraPaymentsEnabled($organization),
                'voucher_top_up' => $this->isVoucherTopUpEnabled($organization),
                'email_connection' => true,
                'external_funds' => true,
                'budget_funds' => true,
                'subsidy_funds' => true,
            ]
        ];
    }


    /**
     * @param Organization $organization
     * @return bool
     */
    protected function isReimbursementsEnabled(Organization $organization): bool
    {
        return $organization->funds
            ->filter(fn(Fund $fund) => $fund->fund_config->allow_reimbursements)
            ->isNotEmpty();
    }

    /**
     * @param Organization $organization
     * @return bool
     */
    protected function isVoucherRecordsEnabled(Organization $organization): bool
    {
        return $organization->funds
            ->filter(fn(Fund $fund) => $fund->fund_config->allow_voucher_records)
            ->isNotEmpty();
    }

    /**
     * @param Organization $organization
     * @return bool
     */
    protected function isPhysicalCardsEnabled(Organization $organization): bool
    {
        return $organization->funds
            ->filter(fn(Fund $fund) => $fund->fund_config->allow_physical_cards)
            ->isNotEmpty();
    }

    /**
     * @param Organization $organization
     * @return bool
     */
    protected function isIConnectApiOinEnabled(Organization $organization): bool
    {
        return $organization->funds
            ->filter(fn(Fund $fund) => $fund->hasIConnectApiOin())
            ->isNotEmpty();
    }

    /**
     * @param Organization $organization
     * @return bool
     */
    protected function isDigidEnabled(Organization $organization): bool
    {
        return $organization->implementations
            ->filter(fn(Implementation $implementation) => $implementation->digidEnabled())
            ->isNotEmpty();
    }

    /**
     * @param Organization $organization
     * @return bool
     */
    protected function isExtraPaymentsEnabled(Organization $organization): bool
    {
        return $organization->allow_provider_extra_payments;
    }

    /**
     * @param Organization $organization
     * @return bool
     */
    protected function isFundRequestsEnabled(Organization $organization): bool
    {
        return $organization->funds
            ->filter(fn(Fund $fund) => $fund->fund_config->allow_fund_requests)
            ->isNotEmpty();
    }

    /**
     * @param Organization $organization
     * @return bool
     */
    protected function isVoucherTopUpEnabled(Organization $organization): bool
    {
        return $organization->funds
            ->filter(fn(Fund $fund) => $fund->fund_config->allow_voucher_top_ups)
            ->isNotEmpty();
    }
}