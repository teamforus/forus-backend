<?php

namespace App\Listeners;

use App\Events\FundRequestClarifications\FundRequestClarificationReceived;
use App\Events\FundRequestClarifications\FundRequestClarificationRequested;
use App\Events\FundRequestRecords\FundRequestRecordApproved;
use App\Events\FundRequestRecords\FundRequestRecordAssigned;
use App\Events\FundRequestRecords\FundRequestRecordUpdated;
use App\Events\FundRequestRecords\FundRequestRecordDeclined;
use App\Events\FundRequestRecords\FundRequestRecordResigned;
use App\Events\FundRequests\FundRequestAssigned;
use App\Events\FundRequests\FundRequestCreated;
use App\Events\FundRequests\FundRequestResigned;
use App\Events\FundRequests\FundRequestResolved;
use App\Models\Employee;
use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\FundRequestRecord;
use App\Notifications\Identities\Employee\IdentityAssignedToFundRequestBySupervisorNotification;
use App\Notifications\Identities\FundRequest\IdentityFundRequestApprovedNotification;
use App\Notifications\Identities\FundRequest\IdentityFundRequestCreatedNotification;
use App\Notifications\Identities\FundRequest\IdentityFundRequestDeniedNotification;
use App\Notifications\Identities\FundRequest\IdentityFundRequestDisregardedNotification;
use App\Notifications\Identities\FundRequest\IdentityFundRequestRecordDeclinedNotification;
use App\Notifications\Identities\FundRequest\IdentityFundRequestRecordFeedbackRequestedNotification;
use App\Notifications\Organizations\FundRequests\FundRequestCreatedValidatorNotification;
use App\Notifications\Organizations\FundRequests\FundRequestRecordFeedbackReceivedNotification;
use App\Scopes\Builders\FundQuery;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Gate;

class FundRequestSubscriber
{


    /**
     * @param FundRequest $fundRequest
     * @param array $extraModels
     *
     * @return (Fund|FundRequest|\App\Models\Implementation|\App\Models\Organization|mixed)[]
     *
     * @psalm-return array{fund: Fund|mixed, sponsor: \App\Models\Organization|mixed, fund_request: FundRequest|mixed, implementation: \App\Models\Implementation|mixed,...}
     */
    private function getFundRequestLogModels(
        FundRequest $fundRequest,
        array $extraModels = []
    ): array {
        return array_merge([
            'fund' => $fundRequest->fund,
            'sponsor' => $fundRequest->fund->organization,
            'fund_request' => $fundRequest,
            'implementation' => $fundRequest->fund->getImplementation(),
        ], $extraModels);
    }

    /**
     * @param FundRequestRecord $fundRequestRecord
     * @param array $extraModels
     *
     * @return (FundRequestRecord|mixed)[]
     *
     * @psalm-return array{fund_request_record: FundRequestRecord|mixed,...}
     */
    private function getFundRequestRecordLogModels(
        FundRequestRecord $fundRequestRecord,
        array $extraModels = []
    ): array {
        return array_merge($this->getFundRequestLogModels($fundRequestRecord->fund_request), array_merge([
            'fund_request_record' => $fundRequestRecord,
        ], $extraModels));
    }

    /**
     * @param Employee $supervisor
     *
     * @return (int|null|string)[]
     *
     * @psalm-return array{supervisor_employee_id: int, supervisor_employee_roles: string, supervisor_employee_email: null|string}
     */
    private function getSupervisorFields(Employee $supervisor): array
    {
        return [
            'supervisor_employee_id' => $supervisor->id,
            'supervisor_employee_roles' => $supervisor->roles->pluck('name')->join(', '),
            'supervisor_employee_email' => $supervisor->identity?->email,
        ];
    }
}
