<?php

namespace App\Observers;

use App\Events\FundProviders\FundProviderApprovedBudget;
use App\Events\FundProviders\FundProviderApprovedProducts;
use App\Events\FundProviders\FundProviderRevokedBudget;
use App\Events\FundProviders\FundProviderRevokedProducts;
use App\Models\FundProvider;

class FundProviderObserver
{
    /**
     * Handle the fund provider "created" event.
     *
     * @param  \App\Models\FundProvider  $fundProvider
     * @return void
     */
    public function created(FundProvider $fundProvider)
    {
        //
    }

    /**
     * Handle the fund provider "updated" event.
     *
     * @param  \App\Models\FundProvider  $fundProvider
     * @return void
     */
    public function updated(FundProvider $fundProvider)
    {
        if ($fundProvider->wasChanged('allow_budget')) {
            if ($fundProvider->allow_budget) {
                FundProviderApprovedBudget::dispatch($fundProvider);
            } else {
                FundProviderRevokedBudget::dispatch($fundProvider);
            }
        }

        if ($fundProvider->wasChanged('allow_products')) {
            if ($fundProvider->allow_products) {
                FundProviderApprovedProducts::dispatch($fundProvider);
            } else {
                FundProviderRevokedProducts::dispatch($fundProvider);
            }
        }
    }
}
