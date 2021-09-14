<?php

namespace App\Notifications\Organizations\FundProviders;

/**
 * Class FundProvidersApprovedProductsNotification
 * @package App\Notifications\Organizations\FundProviders
 */
class FundProvidersApprovedProductsNotification extends BaseFundProvidersNotification
{
    protected static $key = 'notifications_fund_providers.approved_products';
    protected static $permissions = 'manage_provider_funds';

    protected static $visible = true;
}
