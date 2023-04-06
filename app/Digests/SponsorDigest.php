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
use Illuminate\Support\Facades\Config;

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
     * When at least one provider applied to one of your funds,
     * send info about lack of activity on your other funds
     */
    protected bool $notifyNoProviderActivity;

    /**
     * SponsorDigest constructor.
     */
    public function __construct()
    {
        $this->notifyNoProviderActivity = Config::get('forus.digest.sponsor.notify_no_provider_activity', false);
    }

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
            'organization_name' => $organization->name
        ]), ["margin_less"]);

        [$emailBodyApplications, $total_applications] = $this->getApplicationsEmailBody($organization);
        [$emailBodyProductsAdded, $total_products_added] = $this->getProductsAddedEmailBody($organization);
        [$emailBodyProductsPending, $total_products_pending] = $this->getProductsPendingEmailBody($organization);
        [$emailBodyProvidersReply, $total_messages] = $this->getProvidersReplyEmailBody($organization);

        if (($total_applications + $total_products_added + $total_products_pending + $total_messages) === 0) {
            return;
        }

        $emailBody = $emailBody->merge(
            $emailBodyApplications,
            $emailBodyProductsAdded,
            $emailBodyProductsPending,
            $emailBodyProvidersReply,
        );

        $emailBody->button_primary(
            Implementation::general()->url_sponsor,
            trans('digests/sponsor.dashboard_button')
        );

        $this->sendOrganizationDigest($organization, $emailBody, $notificationService);
    }

    /**
     * @param Organization $organization
     * @return array
     */
    private function getApplicationsEmailBody(Organization $organization): array
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
            /** @var Fund $fund */
            /** @var int $countEvents */
            /** @var array[]|Collection $eventLogs */
            foreach ($applyEvents as [$fund, $countEvents, $eventLogs]) {
                if ($eventLogs->count() > 0) {
                    $emailBody->h3(trans('digests/sponsor.providers_header', [
                        'fund_name' => $fund->name,
                    ]), ['margin_less']);

                    $emailBody->text(trans_choice('digests/sponsor.providers', $countEvents, [
                        'fund_name' => $fund->name,
                        'providers_count' => $countEvents,
                        'providers_list' => $eventLogs->pluck('provider_name')->implode("\n- "),
                    ]));
                } else if ($this->notifyNoProviderActivity) {
                    $emailBody->h3(trans('digests/sponsor.providers_header_empty', [
                        'fund_name' => $fund->name,
                    ]), ['margin_less'])->text(trans('digests/sponsor.providers_empty'));
                }
            }
        }

        return [$emailBody, $total_applications];
    }

    /**
     * @param Organization $organization
     * @return array
     */
    private function getProductsAddedEmailBody(Organization $organization): array
    {
        $events = [];
        $emailBody = new MailBodyBuilder();

        foreach ($organization->funds as $fund) {
            $query = EventLog::eventsOfTypeQuery(Fund::class, $fund->id);
            $query->where('event', Fund::EVENT_PRODUCT_ADDED);
            $query->where('created_at', '>=', $this->getLastOrganizationDigestTime($organization));

            $events[] = [$fund, $query->count(), $query->get()];
        }

        $total_products_added = array_sum(array_pluck($events, '1'));

        if ($total_products_added > 0) {
            $emailBody->separator();

            /** @var Fund $fund */
            /** @var int $countEvents */
            /** @var EventLog[]|Collection $eventLogs */
            foreach ($events as [$fund, $countEvents, $eventLogs]) {
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
        }

        return [$emailBody, $total_products_added];
    }

    /**
     * @param Organization $organization
     * @return array
     */
    private function getProductsPendingEmailBody(Organization $organization): array
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

            /** @var Fund $fund */
            /** @var int $countEvents */
            /** @var EventLog[]|Collection $eventLogs */
            foreach ($events as [$fund, $countEvents, $eventLogs]) {
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
        }

        return [$emailBody, $events->sum(1)];
    }

    /**
     * @param Organization $organization
     * @return array
     */
    private function getProvidersReplyEmailBody(Organization $organization): array
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

            /** @var Fund $fund */
            /** @var int $countEvents */
            /** @var EventLog[]|Collection $eventLogs */
            foreach ($events as [$fund, $countEvents, $eventLogs]) {
                $emailBody->h3(trans_choice('digests/sponsor.feedback_header', $countEvents, [
                    'count_messages' => $countEvents,
                    'fund_name' => $fund->name,
                ]));

                $logsByProvider = $eventLogs->pluck('data')->groupBy('provider_id');

                foreach ($logsByProvider as $logs) {
                    $logsByProduct = collect($logs)->groupBy('product_id');
                    $emailBody->h5(trans("digests/sponsor.feedback_item_header", $logs[0]), [
                        'margin_less'
                    ]);

                    foreach ($logsByProduct as $_logsByProduct) {
                        $emailBody->text(trans_choice('digests/sponsor.feedback_item', count(
                            $logsByProduct
                        ), array_merge([
                            'count_messages' => count($logsByProduct)
                        ], $_logsByProduct[0])));
                    }
                }

                $emailBody = $emailBody->space();
            }
        }

        return [$emailBody, $total_messages];
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