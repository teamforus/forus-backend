<?php

namespace Tests\Traits;

use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Organization;

trait MakesTestFundProviders
{
    /**
     * @param Organization $providerOrganization
     * @param Fund $fund
     * @return FundProvider
     */
    private function makeTestFundProvider(Organization $providerOrganization, Fund $fund): FundProvider
    {
        return FundProvider::create([
            'state' => FundProvider::STATE_ACCEPTED,
            'fund_id' => $fund->id,
            'allow_budget' => true,
            'organization_id' => $providerOrganization->id,
            'allow_products' => true,
        ])->refresh();
    }
}