<?php

namespace App\Notifications\Organizations\FundProviders;

use App\Mail\Funds\ProviderRejectedMail;
use App\Models\FundProvider;
use App\Models\Identity;

/**
 * Notify the fund provider that they can no longer scan budget vouchers
 */
class FundProvidersRevokedBudgetNotification extends BaseFundProvidersNotification
{
    protected static ?string $key = 'notifications_fund_providers.revoked_budget';
    protected static string|array $permissions = 'manage_provider_funds';

    public function toMail(Identity $identity): void
    {
        /** @var FundProvider $fundProvider */
        $fundProvider = $this->eventLog->loggable;
        $fund = $fundProvider->fund;

        if (!$fundProvider->isAccepted()) {
            return;
        }

        $mailable = new ProviderRejectedMail([
            ...$this->eventLog->data,
            'provider_dashboard_link' => $fund->urlProviderDashboard(),
        ], $fund->fund_config->implementation->getEmailFrom());

        $this->sendMailNotification($identity->email, $mailable, $this->eventLog);
    }
}
