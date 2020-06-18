<?php

namespace App\Notifications\Organizations\FundProviders;

use App\Mail\Funds\ProviderRejectedMail;
use App\Models\FundProvider;
use App\Services\Forus\Identity\Models\Identity;

/**
 * Class FundProvidersRevokedBudgetNotification
 * @package App\Notifications\Organizations\FundProviders
 */
class FundProvidersRevokedBudgetNotification extends BaseFundProvidersNotification
{
    protected $key = 'notifications_fund_providers.revoked_budget';
    protected static $permissions = [
        'manage_provider_funds'
    ];

    public function toMail(Identity $identity)
    {
        /** @var FundProvider $fundProvider */
        $fundProvider = $this->eventLog->loggable;
        $fund = $fundProvider->fund;

        resolve('forus.services.notification')->sendMailNotification(
            $identity->primary_email->email,
            new ProviderRejectedMail(
                $this->eventLog->data['fund_name'],
                $this->eventLog->data['provider_name'],
                $this->eventLog->data['sponsor_name'],
                $this->eventLog->data['sponsor_phone'],
                $fund->fund_config->implementation->getEmailFrom()
            )
        );
    }
}
