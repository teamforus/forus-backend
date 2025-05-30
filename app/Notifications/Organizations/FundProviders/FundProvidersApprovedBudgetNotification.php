<?php

namespace App\Notifications\Organizations\FundProviders;

use App\Mail\Funds\ProviderApprovedMail;
use App\Models\FundProvider;
use App\Models\Identity;

/**
 * Notify fund provider that they can scan budget vouchers now.
 */
class FundProvidersApprovedBudgetNotification extends BaseFundProvidersNotification
{
    protected static ?string $key = 'notifications_fund_providers.approved_budget';
    protected static ?string $pushKey = 'funds.provider_approved';

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

        if (!$fundProvider->isAccepted()) {
            return;
        }

        $mailable = new ProviderApprovedMail([
            ...$this->eventLog->data,
            'provider_dashboard_link' => $fund->urlProviderDashboard(),
        ], $fund->fund_config->implementation->getEmailFrom());

        $this->sendMailNotification($identity->email, $mailable, $this->eventLog);
    }
}
