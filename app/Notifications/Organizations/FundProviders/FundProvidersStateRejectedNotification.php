<?php

namespace App\Notifications\Organizations\FundProviders;

use App\Mail\Funds\ProviderStateRejectedMail;
use App\Models\FundProvider;
use App\Models\Identity;
use App\Models\Permission;

/**
 * Notify fund provider that they can scan budget vouchers now.
 */
class FundProvidersStateRejectedNotification extends BaseFundProvidersNotification
{
    protected static ?string $key = 'notifications_fund_providers.state_rejected';
    protected static ?string $pushKey = 'fund_providers.state_rejected';

    /**
     * @var string[]
     */
    protected static string|array $permissions = Permission::MANAGE_PROVIDER_FUNDS;

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        /** @var FundProvider $fundProvider */
        $fundProvider = $this->eventLog->loggable;
        $fund = $fundProvider->fund;

        $mailable = new ProviderStateRejectedMail([
            ...$this->eventLog->data,
            'provider_dashboard_link' => $fund->urlProviderDashboard(),
        ], $fund->fund_config->implementation->getEmailFrom());

        $this->sendMailNotification($identity->email, $mailable, $this->eventLog);
    }
}
