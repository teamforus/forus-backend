<?php

namespace App\Notifications\Organizations\FundProviders;

/***
 * Class FundProviderFundEndedNotification
 * @package App\Notifications\Organizations\FundProviders
 */
class FundProviderTransactionBunqSuccessNotification extends BaseFundProvidersNotification
{
    protected static $key = 'notifications_fund_providers.bunq_transaction_success';
    protected static $pushKey = 'bunq.transaction_success';
    protected static $scope = null;
    protected static $sendPush = true;
    protected static $permissions = 'view_finances';

    protected static $visible = true;
}
