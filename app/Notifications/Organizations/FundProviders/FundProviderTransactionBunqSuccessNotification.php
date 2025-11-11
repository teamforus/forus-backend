<?php

namespace App\Notifications\Organizations\FundProviders;

use App\Models\Permission;

/***
 * Bunq transaction completed
 */
class FundProviderTransactionBunqSuccessNotification extends BaseFundProvidersNotification
{
    protected static ?string $key = 'notifications_fund_providers.bunq_transaction_success';
    protected static ?string $scope = null;
    protected static string|array $permissions = Permission::VIEW_FINANCES;
}
