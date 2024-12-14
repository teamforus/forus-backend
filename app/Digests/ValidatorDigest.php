<?php

namespace App\Digests;

use App\Mail\Digest\BaseDigestMail;
use App\Mail\Digest\DigestValidatorMail;
use App\Mail\MailBodyBuilder;
use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Permission;
use App\Services\EventLogService\Models\EventLog;
use App\Services\Forus\Notification\NotificationService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Collection;

class ValidatorDigest extends BaseOrganizationDigest
{
    use Dispatchable;

    protected string $requiredRelation = "funds";
    protected string $digestKey = "validator";

    protected array $employeePermissions = [
        Permission::VALIDATE_RECORDS,
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
        $digestDateTime = $this->getLastOrganizationDigestTime($organization);

        return $organization->funds()->where([
            'state' => Fund::STATE_ACTIVE,
        ])->get()->map(static function(Fund $fund) use ($digestDateTime) {
            $query = EventLog::eventsOfTypeQuery(FundRequest::class, $fund->fund_requests());
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