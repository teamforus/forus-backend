<?php

namespace App\Notifications\Organizations\FundProviders;

/***
 * Bunq transaction completed
 */
class FundProviderTransactionBunqSuccessNotification extends BaseFundProvidersNotification
{
    protected static $key = 'notifications_fund_providers.bunq_transaction_success';
    protected static $scope = null;
    protected static $permissions = 'view_finances';
}
