<?php


namespace App\Digests;

use App\Mail\Digest\BaseDigestMail;
use App\Mail\Digest\DigestValidatorMail;
use App\Mail\MailBodyBuilder;
use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\Implementation;
use App\Models\Organization;
use App\Services\EventLogService\Models\EventLog;
use App\Services\Forus\Notification\NotificationService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Collection;

/**
 * Class ValidatorDigest
 * @package App\Digests
 */
class ValidatorDigest extends BaseOrganizationDigest
{
    use Dispatchable;

    protected $requiredRelation = "funds";
    protected $digestKey = "validator";
    protected $employeePermissions = [
        'validate_records'
    ];

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
                "%s nieuwe aanvragen voor %s",
                $event['eventsCount'],
                $event['fund']->name
            ));

            $emailBody->text(sprintf(
                "U heeft %s nieuwe aanvragen wachtende op uw dashboard.\n" .
                "Ga naar het dashboard om deze aanvragen goed te keuren.",
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
     * @param MailBodyBuilder $emailBody
     * @return BaseDigestMail
     */
    protected function getDigestMailable(MailBodyBuilder $emailBody): BaseDigestMail
    {
        return new DigestValidatorMail(compact('emailBody'));
    }
}