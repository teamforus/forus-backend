<?php

namespace App\Digests;

use App\Mail\Digest\BaseDigestMail;
use App\Mail\Digest\DigestSponsorMail;
use App\Mail\MailBodyBuilder;
use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Product;
use App\Scopes\Builders\FundProviderQuery;
use App\Scopes\Builders\ProductQuery;
use App\Services\EventLogService\Models\EventLog;
use App\Services\Forus\Notification\NotificationService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Collection;

/**
 * Class SponsorDigest
 * @package App\Digests
 */
class SponsorDigest extends BaseOrganizationDigest
{
    use Dispatchable;

    protected string $requiredRelation = 'funds';
    protected string $digestKey = 'sponsor';
    protected array $employeePermissions = [
        'manage_providers'
    ];

    /**
     * @param Organization $organization
     * @param NotificationService $notificationService
     */
    public function handleOrganizationDigest(
        Organization $organization,
        NotificationService $notificationService
    ): void {
        $emailBody = new MailBodyBuilder();
        $emailBody->h1(trans('digests/sponsor.title'));
        $emailBody->text(trans('digests/sponsor.greetings', [
            'organization_name' => $organization->name,
        ]), ["margin_less"]);

        $groups = collect([
            $this->getApplicationsEmailBody($organization),
            $this->getProductsPendingEmailBody($organization),
            $this->getProductsAddedEmailBody($organization),
            $this->getProvidersReplyEmailBody($organization),
        ])->filter();

        if ($groups->count() > 0) {
            $emailBody = $groups->reduce(function (MailBodyBuilder $emailBody, MailBodyBuilder $group) {
                return $emailBody->merge($group);
            }, $emailBody);

            $emailBody->separator();
            $emailBody->button_primary(
                Implementation::general()->urlSponsorDashboard(),
                trans('digests/sponsor.dashboard_button')
            );

            $this->sendOrganizationDigest($organization, $emailBody, $notificationService);
        }
    }

    /**
     * @param Organization $organization
     * @return MailBodyBuilder|null
     */
    private function getApplicationsEmailBody(Organization $organization): ?MailBodyBuilder
    {
        $applyEvents = [];
        $emailBody = new MailBodyBuilder();

        foreach ($organization->funds as $fund) {
            $query = EventLog::eventsOfTypeQuery(Fund::class, $fund->id);
            $query->where('event', Fund::EVENT_PROVIDER_APPLIED);
            $query->where('created_at', '>=', $this->getLastOrganizationDigestTime($organization));

            $applyEvents[] = [$fund, $query->count(), $query->pluck('data')];
        }

        $total_applications = array_sum(array_pluck($applyEvents, '1'));

        if ($total_applications > 0) {
            $emailBody->separator();
            $emailBody->h2(trans('digests/sponsor.providers.title'));

            /** @var Fund $fund */
            /** @var int $countEvents */
            /** @var array[]|Collection $eventLogs */
            foreach ($applyEvents as [$fund, $countEvents, $eventLogs]) {
                if ($countEvents < 1) {
                    continue;
                }

                $emailBody->h3(trans('digests/sponsor.providers.header', [
                    'fund_name' => $fund->name,
                ]), ['margin_less']);

                $emailBody->text(trans_choice('digests/sponsor.providers.details', $countEvents, [
                    'fund_name' => $fund->name,
                    'providers_count' => $countEvents,
                    'providers_list' => $eventLogs->pluck('provider_name')->implode("\n- "),
                ]));
            }

            return $emailBody;
        }

        return null;
    }

    /**
     * @param Organization $organization
     * @return MailBodyBuilder|null
     */
    private function getProductsAddedEmailBody(Organization $organization): ?MailBodyBuilder
    {
        $emailBody = new MailBodyBuilder();

        $events = $organization->funds->map(function(Fund $fund) use ($organization) {
            $providerQuery = FundProviderQuery::whereApprovedForFundsFilter(
                FundProvider::query(), $fund->id,
            )->select('organization_id');

            $productsQuery = Product::query()->whereIn('organization_id', $providerQuery);
            $productsQuery = ProductQuery::approvedForFundsFilter($productsQuery, $fund->id);
            $productsQuery = ProductQuery::inStockAndActiveFilter($productsQuery);

            $query = EventLog::eventsOfTypeQuery(Product::class, $productsQuery);
            $query->where('event', Product::EVENT_CREATED);
            $query->where('created_at', '>=', $this->getLastOrganizationDigestTime($organization));

            return [$fund, $query->count(), $query->get()];
        });

        if ($events->sum(1) > 0) {
            $emailBody->separator();
            $emailBody->h2(trans('digests/sponsor.products.title'));

            /** @var Fund $fund */
            /** @var int $countEvents */
            /** @var EventLog[]|Collection $eventLogs */
            foreach ($events as [$fund, $countEvents, $eventLogs]) {
                if ($countEvents < 1) {
                    continue;
                }

                $emailBody->h3(trans('digests/sponsor.products.header', [
                    'fund_name' => $fund->name,
                ]), ['margin_less']);

                $emailBody->text(trans_choice('digests/sponsor.products.details', $countEvents, [
                    'fund_name' => $fund->name,
                    'products_count' => $countEvents,
                ]));

                $eventLogsByProvider = $eventLogs->pluck('data')->groupBy('provider_id');

                /** @var array $event_item */
                foreach ($eventLogsByProvider as $event_items) {
                    $emailBody->h5(trans_choice(
                        'digests/sponsor.products.provider',
                        count($event_items), [
                        'provider_name' => $event_items[0]['provider_name'],
                        'products_count' => count($event_items)
                    ]), ['margin_less']);

                    $emailBody->text("- " . implode("\n- ", array_map(static function ($data) {
                        return trans('digests/sponsor.products.item', $data);
                    }, $event_items->toArray())));
                }
            }

            return $emailBody;
        }

        return null;
    }

    /**
     * @param Organization $organization
     * @return MailBodyBuilder|null
     */
    private function getProductsPendingEmailBody(Organization $organization): ?MailBodyBuilder
    {
        $emailBody = new MailBodyBuilder();

        $events = $organization->funds->map(function(Fund $fund) use ($organization) {
            $providerQuery = FundProviderQuery::whereApprovedForFundsFilter(
                FundProvider::query(), $fund->id,
            )->select('organization_id');

            $productsQuery = Product::query()->whereIn('organization_id', $providerQuery);
            $productsQuery = ProductQuery::notApprovedForFundsFilter($productsQuery, $fund->id);
            $productsQuery = ProductQuery::inStockAndActiveFilter($productsQuery);

            $query = EventLog::eventsOfTypeQuery(Product::class, $productsQuery);
            $query->where('event', Product::EVENT_CREATED);
            $query->where('created_at', '>=', $this->getLastOrganizationDigestTime($organization));

            return [$fund, $query->count(), $query->get()];
        });

        if ($events->sum(1) > 0) {
            $emailBody->separator();
            $emailBody->h2(trans('digests/sponsor.products_pending.title'));

            /** @var Fund $fund */
            /** @var int $countEvents */
            /** @var EventLog[]|Collection $eventLogs */
            foreach ($events as [$fund, $countEvents, $eventLogs]) {
                if ($countEvents < 1) {
                    continue;
                }

                $emailBody->h3(trans('digests/sponsor.products_pending.header', [
                    'fund_name' => $fund->name,
                ]), ['margin_less']);

                $emailBody->text(trans_choice('digests/sponsor.products_pending.details', $countEvents, [
                    'fund_name' => $fund->name,
                    'products_count' => $countEvents,
                ]));

                $eventLogsByProvider = $eventLogs->pluck('data')->groupBy('provider_id');

                /** @var array $event_item */
                foreach ($eventLogsByProvider as $event_items) {
                    $emailBody->h5(trans_choice(
                        'digests/sponsor.products_pending.provider',
                        count($event_items), [
                        'provider_name' => $event_items[0]['provider_name'],
                        'products_count' => count($event_items)
                    ]), ['margin_less']);

                    $emailBody->text("- " . implode("\n- ", array_map(static function ($data) {
                        return trans('digests/sponsor.products_pending.item', $data);
                    }, $event_items->toArray())));
                }
            }

            return $emailBody;
        }

        return null;
    }

    /**
     * @param Organization $organization
     * @return MailBodyBuilder|null
     */
    private function getProvidersReplyEmailBody(Organization $organization): ?MailBodyBuilder
    {
        $events = [];
        $emailBody = new MailBodyBuilder();

        foreach ($organization->funds as $fund) {
            $query = EventLog::eventsOfTypeQuery(Fund::class, $fund->id);
            $query->where('event', Fund::EVENT_PROVIDER_REPLIED);
            $query->where('created_at', '>=', $this->getLastOrganizationDigestTime($organization));
            $events[] = [$fund, $query->count(), $query->get()];
        }

        $total_messages = array_sum(array_pluck($events, '1'));

        if ($total_messages > 0) {
            $emailBody->separator();
            $emailBody->h2(trans('digests/sponsor.feedback.title'));

            /** @var Fund $fund */
            /** @var int $countEvents */
            /** @var EventLog[]|Collection $eventLogs */
            foreach ($events as [$fund, $countEvents, $eventLogs]) {
                if ($countEvents < 1) {
                    continue;
                }

                $emailBody->h3(trans_choice('digests/sponsor.feedback.header', $countEvents, [
                    'count_messages' => $countEvents,
                    'fund_name' => $fund->name,
                ]));

                $logsByProvider = $eventLogs->pluck('data')->groupBy('provider_id');

                foreach ($logsByProvider as $logs) {
                    $logsByProduct = collect($logs)->groupBy('product_id');
                    $emailBody->h5(trans("digests/sponsor.feedback.item_header", $logs[0]), [
                        'margin_less'
                    ]);

                    foreach ($logsByProduct as $_logsByProduct) {
                        $emailBody->text(trans_choice('digests/sponsor.feedback.item', count(
                            $logsByProduct
                        ), array_merge([
                            'count_messages' => count($logsByProduct)
                        ], $_logsByProduct[0])));
                    }
                }

                $emailBody = $emailBody->space();
            }

            return $emailBody;
        }

        return null;
    }

    /**
     * @param MailBodyBuilder $emailBody
     * @return BaseDigestMail
     */
    protected function getDigestMailable(MailBodyBuilder $emailBody): BaseDigestMail
    {
        return new DigestSponsorMail(compact('emailBody'));
    }
}