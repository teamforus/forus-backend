<?php


namespace App\Digests;

use App\Mail\MailBodyBuilder;
use App\Models\Employee;
use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Organization;
use App\Services\EventLogService\Models\EventLog;
use App\Services\Forus\Identity\Models\Identity;
use App\Services\Forus\Notification\NotificationService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Collection;

/**
 * Class RequesterDigest
 * @package App\Digests
 */
class SponsorDigest
{
    use Dispatchable;

    /**
     * @param NotificationService $notificationService
     */
    public function handle(NotificationService $notificationService): void
    {
        $organizations = Organization::whereHas('funds')->get();

        foreach ($organizations as $organization) {
            $this->handleOrganizationDigest($organization, $notificationService);
        }
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
     * @return \Carbon\Carbon|\Illuminate\Support\Carbon
     */
    public function getOrganizationDigestTime(
        Organization $organization
    ) {
        return $organization->lastDigestOfType('sponsor')->created_at ?? now()->subDay();
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
                    "New provider applications for %s",
                    $fund->name
                ), ['margin_less']);

                $emailBody->text(sprintf(
                    "%s provider(s) applied for being accepted for %s\n- %s",
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
                    "New products added for %s webshop.",
                    $fund->name
                ), ['margin_less']);

                $emailBody->text(sprintf(
                    "%s product(s) added that areeligible for %s.",
                    $countEvents,
                    $fund->name
                ));

                $eventLogsByProvider = $eventLogs->pluck('data')->groupBy('provider_id');

                /** @var array $event_item */
                foreach ($eventLogsByProvider as $event_items) {
                    $emailBody->h5(sprintf(
                        "%s (%s product(s))",
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
                    "%s new replies on your feedback of %s",
                    $countEvents,
                    $fund->name
                ));
                $logsByProvider = $eventLogs->pluck('data')->groupBy('provider_id');

                foreach ($logsByProvider as $logs) {
                    $emailBody->h5(sprintf(
                        "New message(s) from %s",
                        $logs[0]['provider_name']
                    ), ['margin_less']);
                    $logsByProduct = collect($logs)->groupBy('product_id');

                    foreach ($logsByProduct as $_logsByProduct) {
                        $emailBody->text(sprintf(
                            "- %s has sent %s message(s) on %s.",
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

    private function sendOrganizationDigest(
        Organization $organization,
        MailBodyBuilder $emailBody,
        NotificationService $notificationService
    ): void {
        /** @var Employee[] $employees */
        $employees = $organization->employeesWithPermissions('manage_providers');

        foreach ($employees as $employee) {
            if ($identity = Identity::findByAddress($employee->identity_address)) {
                $notificationService->dailyDigestSponsor($identity->email, compact('emailBody'));
            }
        }

        $organization->digests()->create([
            'type' => 'sponsor'
        ]);
    }
}