<?php

namespace App\Notifications\Organizations\FundProviders;

use App\Mail\Funds\ProviderStateAcceptedMail;
use App\Models\FundProvider;
use App\Models\Identity;

/**
 * Notify fund provider that they can scan budget vouchers now.
 */
class FundProvidersStateAcceptedNotification extends BaseFundProvidersNotification
{
    protected static ?string $key = 'notifications_fund_providers.state_accepted';
    protected static ?string $pushKey = 'fund_providers.state_accepted';

    /**
     * @var string[]
     */
    protected static string|array $permissions = 'manage_provider_funds';

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        /** @var FundProvider $fundProvider */
        $fundProvider = $this->eventLog->loggable;
        $fund = $fundProvider->fund;

        $mailable = new ProviderStateAcceptedMail([
            ...$this->eventLog->data,
            'provider_dashboard_link' => $fund->urlProviderDashboard(),
        ], $fund->fund_config->implementation->getEmailFrom());

        $this->sendMailNotification($identity->email, $mailable, $this->eventLog);
    }
}
