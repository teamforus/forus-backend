<?php

namespace App\Notifications\Organizations\FundProviders;

use App\Mail\Funds\ProviderStateUnsubscribedMail;
use App\Models\FundProvider;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Permission;
use App\Notifications\Organizations\BaseOrganizationNotification;

class FundProvidersStateUnsubscribedNotification extends BaseOrganizationNotification
{
    protected static ?string $key = 'notifications_fund_providers.state_unsubscribed';
    protected static ?string $pushKey = 'fund_providers.state_unsubscribed';
    protected static ?string $scope = self::SCOPE_SPONSOR;

    /**
     * @var string[]
     */
    protected static string|array $permissions = Permission::MANAGE_PROVIDERS;

    /**
     * @param FundProvider $loggable
     * @return \App\Models\Organization
     */
    public static function getOrganization(mixed $loggable): Organization
    {
        return $loggable->fund->organization;
    }

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        /** @var FundProvider $fundProvider */
        $fundProvider = $this->eventLog->loggable;
        $fund = $fundProvider->fund;

        $mailable = new ProviderStateUnsubscribedMail([
            ...$this->eventLog->data,
            'sponsor_dashboard_link' => $fund->urlSponsorDashboard(),
        ], $fund->fund_config->implementation->getEmailFrom());

        $this->sendMailNotification($identity->email, $mailable, $this->eventLog);
    }
}
