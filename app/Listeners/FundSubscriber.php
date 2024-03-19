<?php

namespace App\Listeners;

use App\Events\Funds\FundArchivedEvent;
use App\Events\Funds\FundBalanceLowEvent;
use App\Events\Funds\FundBalanceSuppliedEvent;
use App\Events\Funds\FundEndedEvent;
use App\Events\Funds\FundCreatedEvent;
use App\Events\Funds\FundExpiringEvent;
use App\Events\Funds\FundProductAddedEvent;
use App\Events\Funds\FundProductApprovedEvent;
use App\Events\Funds\FundProductRevokedEvent;
use App\Events\Funds\FundProviderApplied;
use App\Events\Funds\FundProviderChatMessageEvent;
use App\Events\Funds\FundProviderInvitedEvent;
use App\Events\Funds\FundStartedEvent;
use App\Events\Funds\FundUnArchivedEvent;
use App\Events\Funds\FundUpdatedEvent;
use App\Events\Funds\FundVouchersExportedEvent;
use App\Mail\Forus\ForusFundCreatedMail;
use App\Mail\Funds\FundBalanceWarningMail;
use App\Mail\Funds\ProviderAppliedMail;
use App\Mail\Funds\ProviderInvitationMail;
use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\OrganizationContact;
use App\Notifications\Organizations\FundProviders\FundProviderFundEndedNotification;
use App\Notifications\Organizations\FundProviders\FundProviderFundExpiringNotification;
use App\Notifications\Organizations\FundProviders\FundProviderFundStartedNotification;
use App\Notifications\Organizations\Funds\BalanceLowNotification;
use App\Notifications\Organizations\Funds\BalanceSuppliedNotification;
use App\Notifications\Organizations\Funds\FundArchivedNotification;
use App\Notifications\Organizations\Funds\FundCreatedNotification;
use App\Notifications\Organizations\Funds\FundEndedNotification;
use App\Notifications\Organizations\Funds\FundExpiringNotification;
use App\Notifications\Organizations\Funds\FundProductAddedNotification;
use App\Notifications\Organizations\Funds\FundProviderAppliedNotification;
use App\Notifications\Organizations\Funds\FundProviderChatMessageNotification;
use App\Notifications\Organizations\Funds\FundStartedNotification;
use App\Notifications\Organizations\Funds\FundUnArchivedNotification;
use App\Scopes\Builders\FundProviderQuery;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Config;

class FundSubscriber
{
    private mixed $notificationService;

    /**
     * @param Fund $fund
     * @param array $extraModels
     *
     * @return (Fund|\App\Models\FundConfig|\App\Models\Implementation|\App\Models\Organization|mixed|null)[]
     *
     * @psalm-return array{fund: Fund|mixed, sponsor: \App\Models\Organization|mixed, fund_config: \App\Models\FundConfig|mixed|null, implementation: \App\Models\Implementation|mixed,...}
     */
    private function getFundLogModels(Fund $fund, array $extraModels = []): array
    {
        return array_merge([
            'fund' => $fund,
            'sponsor' => $fund->organization,
            'fund_config' => $fund->fund_config,
            'implementation' => $fund->getImplementation(),
        ], $extraModels);
    }
}
