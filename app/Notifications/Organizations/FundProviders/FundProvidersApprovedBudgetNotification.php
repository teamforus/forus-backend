<?php

namespace App\Notifications\Organizations\FundProviders;

use App\Mail\Funds\ProviderApprovedMail;
use App\Models\FundProvider;
use App\Services\Forus\Identity\Models\Identity;

/**
 * Notify fund provider that they can scan budget vouchers now
 */
class FundProvidersApprovedBudgetNotification extends BaseFundProvidersNotification
{
    protected static ?string $key = 'notifications_fund_providers.approved_budget';
    protected static ?string $pushKey = 'funds.provider_approved';

    /**
     * @var string[]
     */
    protected static $permissions = 'manage_provider_funds';

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

        $this->sendMailNotification(
            $identity->primary_email->email,
            new ProviderApprovedMail(array_merge($this->eventLog->data, [
                'provider_dashboard_link' => $fund->urlProviderDashboard(),
            ]), $fund->fund_config->implementation->getEmailFrom())
        );
    }
}
