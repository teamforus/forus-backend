<?php

namespace App\Notifications\Organizations\FundProviders;

/***
 * Bunq transaction completed
 */
class FundProviderTransactionBunqSuccessNotification extends BaseFundProvidersNotification
{
    protected static ?string $key = 'notifications_fund_providers.bunq_transaction_success';
    protected static ?string $scope = null;
    protected static $permissions = 'view_finances';
}
