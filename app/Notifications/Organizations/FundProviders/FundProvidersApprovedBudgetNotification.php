<?php

namespace App\Notifications\Organizations\FundProviders;

use App\Mail\Funds\ProviderApprovedMail;
use App\Models\FundProvider;
use App\Services\Forus\Identity\Models\Identity;

/**
 * Class FundProvidersApprovedBudgetNotification
 * @package App\Notifications\Organizations\FundProviders
 */
class FundProvidersApprovedBudgetNotification extends BaseFundProvidersNotification
{
    protected $key = 'notifications_fund_providers.approved_budget';
    protected $sendMail = true;

    protected static $permissions = [
        'manage_provider_funds'
    ];

    public function toMail(Identity $identity): void
    {
        /** @var FundProvider $fundProvider */
        $fundProvider = $this->eventLog->loggable;
        $fund = $fundProvider->fund;

        resolve('forus.services.notification')->sendMailNotification(
            $identity->primary_email->email,
            new ProviderApprovedMail(
                $this->eventLog->data['fund_name'],
                $this->eventLog->data['provider_name'],
                $this->eventLog->data['sponsor_name'],
                $fund->urlProviderDashboard(),
                $fund->fund_config->implementation->getEmailFrom()
            )
        );
    }
}
