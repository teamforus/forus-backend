<?php

namespace App\Policies;

use App\Models\FundRequest;
use App\Models\Identity;
use App\Models\Organization;
use App\Scopes\Builders\EmailLogQuery;
use App\Scopes\Builders\IdentityQuery;
use App\Services\MailDatabaseLoggerService\Models\EmailLog;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

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
            return $this->organizationHasAccessToFundRequest($organization, $fundRequestId);
        }

        if ($identityId = $resources['identity_id'] ?? null) {
            return $this->organizationHasAccessToIdentity($organization, $identityId);
        }

        return $organization->isEmployee($identity);
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
        if (!$organization->isEmployee($identity)) {
            return false;
        }

        if ($fundRequest = $emailLog->getRelatedFundRequest()) {
            return EmailLogQuery::whereFundRequest(EmailLog::query(), $fundRequest)
                ->where('id', $emailLog->id)
                ->exists();
        }

        if ($identity = $emailLog->getRelatedIdentity()) {
            return EmailLogQuery::whereIdentity(EmailLog::query(), $identity)
                ->where('id', $emailLog->id)
                ->exists();
        }

        return false;
    }

    /**
     * @param Organization $organization
     * @param int $identityId
     * @return bool
     */
    protected function organizationHasAccessToIdentity(
        Organization $organization,
        int $identityId,
    ): bool {
        return IdentityQuery::relatedToOrganization(Identity::query()->where([
            'id' => $identityId,
        ]), $organization->id)->exists();
    }

    /**
     * @param Organization $organization
     * @param int $fundRequestId
     * @return bool
     */
    protected function organizationHasAccessToFundRequest(
        Organization $organization,
        int $fundRequestId,
    ): bool {
        $fundRequest = FundRequest::query()->find($fundRequestId);

        return $fundRequest?->fund->organization_id === $organization->id;
    }
}
