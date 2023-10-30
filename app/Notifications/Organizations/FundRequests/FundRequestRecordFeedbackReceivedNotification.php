<?php

namespace App\Notifications\Organizations\FundRequests;

use App\Mail\Funds\FundRequestClarifications\FundRequestClarificationReceivedMail;
use App\Models\FundRequestRecord;
use App\Models\Identity;
use App\Models\Organization;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Support\Collection;

class FundRequestRecordFeedbackReceivedNotification extends BaseFundsRequestsNotification
{
    protected static ?string $key = 'notifications_fund_requests.clarification_received';
    protected static string|array $permissions = 'validate_records';

    /**
     * @param FundRequestRecord $loggable
     * @return Organization
     */
    public static function getOrganization(mixed $loggable): Organization
    {
        return $loggable->fund_request->fund->organization;
    }

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        /** @var FundRequestRecord $fundRequestRecord */
        $fundRequestRecord = $this->eventLog->loggable;
        $fundRequest = $fundRequestRecord->fund_request;

        $link = $fundRequest->fund->urlValidatorDashboard(sprintf(
            'organizations/%s/requests/%s',
            $fundRequest->fund->organization_id,
            $fundRequest->id,
        ));

        $this->sendMailNotification(
            $identity->email,
            new FundRequestClarificationReceivedMail(array_merge($this->eventLog->data, [
                'validator_fund_request_link' => $link,
            ]), $fundRequest->fund->getEmailFrom())
        );
    }

    /**
     * @param FundRequestRecord $loggable
     * @param EventLog $eventLog
     * @return Collection
     * @throws \Exception
     */
    public static function eligibleIdentities($loggable, EventLog $eventLog): Collection
    {
        /** @var Collection|Identity[] $identities */
        $identities = parent::eligibleIdentities($loggable, $eventLog);

        return $identities->where('address', $loggable->employee?->identity_address);
    }
}
