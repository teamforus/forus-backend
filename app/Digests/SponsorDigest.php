<?php


namespace App\Digests;

use App\Mail\Digest\BaseDigestMail;
use App\Mail\Digest\DigestSponsorMail;
use App\Mail\MailBodyBuilder;
use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Organization;
use App\Services\EventLogService\Models\EventLog;
use App\Services\Forus\Notification\NotificationService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Collection;

/**
 * Class RequesterDigest
 * @package App\Digests
 */
class SponsorDigest extends BaseOrganizationDigest
{
    use Dispatchable;

    protected $requiredRelation = 'funds';
    protected $digestKey = 'sponsor';
    protected $employeePermissions = [
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
        $emailBody->h1("Update: New applications for your funds");
        $emailBody->text(sprintf("Beste %s,", $organization->name), ["margin_less"]);

        [$emailBodyApplications, $total_applications] = $this->getApplicationsEmailBody($organization);
        [$emailBodyProductsAdded, $total_products_added] = $this->getProductsAddedEmailBody($organization);
        [$emailBodyProvidersReply, $total_messages] = $this->getProvidersReplyEmailBody($organization);


        if (($total_applications + $total_products_added + $total_messages) === 0) {
            return;
        }

        $emailBody = $emailBody->merge(
            $emailBodyApplications,
            $emailBodyProductsAdded,
            $emailBodyProvidersReply
        );

        $emailBody->button_primary(
            Implementation::general_urls()['url_validator'],
            'GA NAAR HET DASHBOARD'
        );

        $this->sendOrganizationDigest($organization, $emailBody, $notificationService);
    }

    /**
     * @param Organization $organization
     * @return array
     */
    private function getApplicationsEmailBody(
        Organization $organization
    ): array {
        $applyEvents = [];
        $emailBody = new MailBodyBuilder();

        foreach ($organization->funds as $fund) {
            $query = EventLog::eventsOfTypeQuery(Fund::class, $fund->id);
            $query->where('event', Fund::EVENT_PROVIDER_APPLIED);
            $query->where('created_at', '>=', $this->getOrganizationDigestTime($organization));

            $applyEvents[] = [$fund, $query->count(), $query->get()];
        }

        $total_applications = array_sum(array_pluck($applyEvents, '1'));

        if ($total_applications > 0) {
            /** @var Fund $fund */
            /** @var int $countEvents */
            /** @var EventLog[]|Collection $eventLogs */
            foreach ($applyEvents as [$fund, $countEvents, $eventLogs]) {
                $emailBody->h3(sprintf(
                    "Nieuwe aanbieders hebben zich aangemeld %s",
                    $fund->name
                ), ['margin_less']);

                $emailBody->text(sprintf(
                    "%s aanbieder(s) hebben zich aangemeld voor %s\n- %s",
                    $countEvents,
                    $fund->name,
                    $eventLogs->pluck('data.provider_name')->implode("\n- ")
                ));
            }
        }

        return [$emailBody, $total_applications];
    }

    /**
     * @param Organization $organization
     * @return array
     */
    private function getProductsAddedEmailBody(
        Organization $organization
    ): array {
        $events = [];
        $emailBody = new MailBodyBuilder();

        foreach ($organization->funds as $fund) {
            $query = EventLog::eventsOfTypeQuery(Fund::class, $fund->id);
            $query->where('event', Fund::EVENT_PRODUCT_ADDED);
            $query->where('created_at', '>=', $this->getOrganizationDigestTime($organization));

            $events[] = [$fund, $query->count(), $query->get()];
        }

        $total_products_added = array_sum(array_pluck($events, '1'));

        if ($total_products_added > 0) {
            $emailBody->separator();

            /** @var Fund $fund */
            /** @var int $countEvents */
            /** @var EventLog[]|Collection $eventLogs */
            foreach ($events as [$fund, $countEvents, $eventLogs]) {
                $emailBody->h3(sprintf(
                    "Nieuwe product(en) voor %s webshop.",
                    $fund->name
                ), ['margin_less']);

                $emailBody->text(sprintf(
                    "%s product(en) zijn toegevoegd aan %s.",
                    $countEvents,
                    $fund->name
                ));

                $eventLogsByProvider = $eventLogs->pluck('data')->groupBy('provider_id');

                /** @var array $event_item */
                foreach ($eventLogsByProvider as $event_items) {
                    $emailBody->h5(sprintf(
                        "%s (%s product(en))",
                        $event_items[0]['provider_name'],
                        count($event_items)
                    ), ['margin_less']);

                    $emailBody->text("- " . implode("\n- ", array_map(static function ($data) {
                        return $data['product_name'] . ' €' . $data['product_price_locale'];
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
    private function getProvidersReplyEmailBody(
        Organization $organization
    ): array {
        $events = [];
        $emailBody = new MailBodyBuilder();

        foreach ($organization->funds as $fund) {
            $query = EventLog::eventsOfTypeQuery(Fund::class, $fund->id);
            $query->where('event', Fund::EVENT_PROVIDER_REPLIED);
            $query->where('created_at', '>=', $this->getOrganizationDigestTime($organization));
            $events[] = [$fund, $query->count(), $query->get()];
        }

        $total_messages = array_sum(array_pluck($events, '1'));

        if ($total_messages > 0) {
            $emailBody->separator();

            /** @var Fund $fund */
            /** @var int $countEvents */
            /** @var EventLog[]|Collection $eventLogs */
            foreach ($events as [$fund, $countEvents, $eventLogs]) {
                $emailBody->h3(sprintf(
                    "%s nieuwe reacties op de feedback die gegeven is op %s",
                    $countEvents,
                    $fund->name
                ));
                $logsByProvider = $eventLogs->pluck('data')->groupBy('provider_id');

                foreach ($logsByProvider as $logs) {
                    $emailBody->h5(sprintf(
                        "Nieuwe berichten van %s",
                        $logs[0]['provider_name']
                    ), ['margin_less']);
                    $logsByProduct = collect($logs)->groupBy('product_id');

                    foreach ($logsByProduct as $_logsByProduct) {
                        $emailBody->text(sprintf(
                            "- %s heeft %s nieuwe bericht(en) gestuurd over %s.",
                            $_logsByProduct[0]['provider_name'],
                            count($logsByProduct),
                            $_logsByProduct[0]['product_name']
                        ));
                    }
                }

                $emailBody->space();
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