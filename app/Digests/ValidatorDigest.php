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
        $total_requests = $events->sum('count_requests');

        if ($total_requests === 0) {
            return;
        }

        $emailBody = new MailBodyBuilder();
        $emailBody->h1(trans_choice('digests/validator.title', $total_requests, [
            'count_requests' => $total_requests
        ]));
        $emailBody->text(trans_choice('digests/validator.greetings', $total_requests, [
            'organization_name' => $organization->name,
            'count_requests' => $total_requests,
        ]))->space();

        foreach ($events as $event) {
            $emailBody->h3(trans_choice(
                "digests/validator.fund_header",
                $event['count_requests'],
                $event
            ));

            $emailBody->text(trans_choice(
                "digests/validator.fund_details",
                $event['count_requests'],
                $event
            ))->space();
        }

        $emailBody->button_primary(
            Implementation::general()->url_validator,
            trans('digests/validator.dashboard_button')
        );

        $this->sendOrganizationDigest($organization, $emailBody, $notificationService);
    }

    /**
     * @param Organization $organization
     * @return Collection
     */
    public function getOrganizationFundRequestEvents(Organization $organization): Collection
    {
        $digestDateTime = $this->getOrganizationDigestTime($organization);

        return $organization->funds()->where([
            'state' => Fund::STATE_ACTIVE,
        ])->get()->map(static function(Fund $fund) use ($digestDateTime) {
            $fundRequests = $fund->fund_requests()->pluck('id')->toArray();
            $query = EventLog::eventsOfTypeQuery(FundRequest::class, $fundRequests);
            $query->where('event', FundRequest::EVENT_CREATED);
            $query->where('created_at', '>=', $digestDateTime);

            return [
                'fund' => $fund,
                'fund_name' => $fund->name,
                'count_requests' => $query->count(),
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