<?php

namespace App\Notifications\Organizations\FundProviders;

use App\Mail\Funds\ProviderRejectedMail;
use App\Models\FundProvider;
use App\Services\Forus\Identity\Models\Identity;

/**
 * Notify the fund provider that they can no longer scan budget vouchers
 */
class FundProvidersRevokedBudgetNotification extends BaseFundProvidersNotification
{
    protected static $key = 'notifications_fund_providers.revoked_budget';
    protected static $permissions = 'manage_provider_funds';

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
            new ProviderRejectedMail(
                $this->eventLog->data,
                $fund->fund_config->implementation->getEmailFrom()
            )
        );
    }
}
