<?php

namespace App\Services\IdentityProviderService\Commands;

use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use App\Services\IdentityProviderService\Models\IdentityProviderMembership;
use Illuminate\Console\Command;

class VerifyIdentityProviderConnectionState extends Command
{
    protected $signature = 'identity-provider:verify-state {connection : Connection UID}';
    protected $description = 'Check stored connection memberships and report inconsistencies.';

    /**
     * @return int
     */
    public function handle(): int
    {
        $connection = IdentityProviderConnection::where('uid', $this->argument('connection'))
            ->firstOrFail();

        $issues = [];

        $connection->memberships()
            ->with(['external_identity', 'identity'])
            ->orderBy('id')
            ->each(function (IdentityProviderMembership $membership) use ($connection, &$issues): void {
                $externalIdentity = $membership->external_identity;
                $employee = $membership->employee()->withTrashed()->first();

                if ($membership->isRetired() && ($membership->identity_id !== null || $membership->employee_id !== null)) {
                    $issues[] = [$membership->uid, 'Retired membership still has an identity or employee'];
                }

                if ($membership->isRetired()) {
                    if ($externalIdentity?->identity_id && !$externalIdentity->memberships()
                        ->where('claim_state', IdentityProviderMembership::CLAIM_CLAIMED)
                        ->where('identity_id', $externalIdentity->identity_id)
                        ->exists()) {
                        $issues[] = [$membership->uid, 'External account has an identity but no matching claimed membership'];
                    }

                    return;
                }

                if (!$externalIdentity ||
                    $externalIdentity->provider !== $connection->provider ||
                    $externalIdentity->tenant_id !== $connection->tenant_id) {
                    $issues[] = [$membership->uid, 'External account is missing or belongs to another provider or tenant'];
                }

                if ($membership->isClaimed() &&
                    ($membership->identity_id === null || !$employee ||
                        $employee->organization_id !== $connection->organization_id ||
                        $employee->identity_address !== $membership->identity?->address)) {
                    $issues[] = [$membership->uid, 'Linked identity or employee is missing or inconsistent'];
                }

                if ($externalIdentity?->identity_id !== $membership->identity_id) {
                    $issues[] = [$membership->uid, 'External account and membership identity bindings do not match'];
                }
            });

        if ($issues === []) {
            $this->info('No identity-provider state inconsistencies found.');
        } else {
            $this->table(['Membership', 'Issue'], $issues);
        }

        return $issues === [] ? self::SUCCESS : self::FAILURE;
    }
}
