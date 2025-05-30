<?php

namespace App\Policies;

use App\Models\FundRequest;
use App\Models\Identity;
use App\Models\Organization;
use App\Scopes\Builders\EmailLogQuery;
use App\Services\MailDatabaseLoggerService\Models\EmailLog;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;

class EmailLogPolicy
{
    use HandlesAuthorization;

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @param array $resources
     * @return bool
     */
    public function viewAny(Identity $identity, Organization $organization, array $resources): bool
    {
        if ($fundRequestId = $resources['fund_request_id'] ?? null) {
            return Gate::forUser($identity)->allows('viewAsValidator', [
                FundRequest::find($fundRequestId), $organization,
            ]);
        }

        if ($identityId = $resources['identity_id'] ?? null) {
            return Gate::forUser($identity)->allows('showSponsorIdentities', [
                $organization, Identity::firstWhere('id', $identityId),
            ]);
        }

        return false;
    }

    /**
     * Determine whether the user can export email logs.
     *
     * @param Identity $identity
     * @param EmailLog $emailLog
     * @param Organization $organization
     * @return Response|bool
     */
    public function export(
        Identity $identity,
        EmailLog $emailLog,
        Organization $organization
    ): Response|bool {
        $logs = EmailLog::where('id', $emailLog->id);
        $fundRequest = $emailLog->getRelatedFundRequest();
        $relatedIdentity = $emailLog->getRelatedIdentity();

        if ($fundRequest) {
            return
                EmailLogQuery::whereFundRequest($logs, $fundRequest, $organization)->exists() &&
                Gate::forUser($identity)->allows('viewAsValidator', [$fundRequest, $organization]);
        }

        if ($relatedIdentity) {
            return
                EmailLogQuery::whereIdentity($logs, $relatedIdentity, $organization)->exists() &&
                Gate::forUser($identity)->allows('showSponsorIdentities', [$organization, $relatedIdentity]);
        }

        return false;
    }
}
