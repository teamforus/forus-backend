<?php

namespace App\Notifications\Organizations\FundProviders;

use App\Mail\Funds\ProviderApprovedMail;
use App\Mail\Funds\ProviderStateAcceptedMail;
use App\Mail\Funds\ProviderStateRejectedMail;
use App\Models\FundProvider;
use App\Services\Forus\Identity\Models\Identity;

/**
 * Notify fund provider that they can scan budget vouchers now
 */
class FundProvidersStateRejectedNotification extends BaseFundProvidersNotification
{
    protected static $key = 'notifications_fund_providers.state_rejected';
    protected static $pushKey = 'fund_providers.state_rejected';

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

        $this->sendMailNotification(
            $identity->primary_email->email,
            new ProviderStateRejectedMail(array_merge($this->eventLog->data, [
                'provider_dashboard_link' => $fund->urlProviderDashboard(),
            ]), $fund->fund_config->implementation->getEmailFrom())
        );
    }
}
