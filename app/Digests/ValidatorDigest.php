<?php


namespace App\Digests;

use App\Mail\MailBodyBuilder;
use App\Models\Employee;
use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\Implementation;
use App\Models\Organization;
use App\Services\EventLogService\Models\EventLog;
use App\Services\Forus\Identity\Models\Identity;
use App\Services\Forus\Notification\NotificationService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Collection;

/**
 * Class ValidatorDigest
 * @package App\Digests
 */
class ValidatorDigest
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
        $events = $this->getOrganizationFundRequestEvents($organization);
        $total_requests = $events->sum('eventsCount');

        if ($total_requests === 0) {
            return;
        }

        $emailBody = new MailBodyBuilder();
        $emailBody->h1(sprintf("Update: %s new validation requests", $total_requests));
        $emailBody->text(sprintf(
            "Beste %s,\n Er zijn %s notificaties die betrekking hebben tot uw organisatie.",
            $organization->name,
            $total_requests
        ))->space();

        foreach ($events as $event) {
            $emailBody->h3(sprintf(
                "%s new validation requests for %s",
                $event['eventsCount'],
                $event['fund']->name
            ));

            $emailBody->text(sprintf(
                "You have %s new validation requests waiting for your on your dashboard.\n" .
                "Please go to your dashboard to check out the applications and accept the sequesters.",
                $event['eventsCount']
            ))->space();
        }

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
        return $organization->lastDigestOfType('validator')->created_at ?? now()->subDay();
    }

    /**
     * @param Organization $organization
     * @return Collection
     */
    public function getOrganizationFundRequestEvents(
        Organization $organization
    ): Collection {
        $digestDateTime = $this->getOrganizationDigestTime($organization);

        return $organization->funds->map(static function(Fund $fund) use ($digestDateTime) {
            $fundRequests = $fund->fund_requests()->pluck('id')->toArray();
            $query = EventLog::eventsOfTypeQuery(FundRequest::class, $fundRequests);
            $query->where('event', FundRequest::EVENT_CREATED);
            $query->where('created_at', '>=', $digestDateTime);

            return [
                'fund' => $fund,
                'eventsCount' => $query->count(),
            ];
        });
    }

    /**
     * @param Organization $organization
     * @param MailBodyBuilder $emailBody
     * @param NotificationService $notificationService
     */
    protected function sendOrganizationDigest(
        Organization $organization,
        MailBodyBuilder $emailBody,
        NotificationService $notificationService
    ): void {
        /** @var Employee[] $employees */
        $employees = $organization->employeesWithPermissions('validate_records');

        foreach ($employees as $employee) {
            if ($identity = Identity::findByAddress($employee->identity_address)) {
                $notificationService->dailyDigestValidator($identity->email, $emailBody);
            }
        }

        $organization->digests()->create([
            'type' => 'validator'
        ]);
    }
}