<?php

namespace App\Services\IdentityProviderService\Services;

use App\Models\IdentityProxy;
use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use App\Services\IdentityProviderService\Models\IdentityProviderMembership;
use App\Services\IdentityProviderService\Models\IdentityProviderOidcSession;
use App\Services\IdentityProviderService\Models\IdentityProviderProxyBinding;
use App\Services\IdentityProviderService\Queries\IdentityProviderOidcSessionQuery;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class IdentityProviderSessionService
{
    /**
     * @param IdentityProxy $sourceProxy
     * @param IdentityProxy $proxy
     * @return void
     */
    public function inheritBinding(IdentityProxy $sourceProxy, IdentityProxy $proxy): void
    {
        $binding = $sourceProxy->identity_provider_binding()->first();

        if ($binding) {
            $connection = IdentityProviderConnection::lockForUpdate()->findOrFail($binding->connection_id);
            $membership = IdentityProviderMembership::lockForUpdate()->findOrFail($binding->membership_id);

            if (
                !$connection->isEnabled() ||
                !$membership->isClaimed() ||
                $membership->connection_id !== $connection->id ||
                $membership->identity?->address !== $proxy->identity_address
            ) {
                abort(403, __('exceptions.forbidden'));
            }
        }

        $sourceProxy = IdentityProxy::lockForUpdate()->find($sourceProxy->id);

        if (!$sourceProxy?->isActive() || $sourceProxy->identity_address !== $proxy->identity_address) {
            abort(403, __('exceptions.forbidden'));
        }

        if ($binding) {
            $proxy->identity_provider_binding()->create($binding->only([
                'connection_id', 'membership_id',
            ]));
        }
    }

    /**
     * @param IdentityProviderConnection|IdentityProviderMembership $owner
     * @return int
     */
    public function invalidateOidcSessions(IdentityProviderConnection|IdentityProviderMembership $owner): int
    {
        $query = IdentityProviderOidcSession::where(function (Builder $query) use ($owner): void {
            if ($owner instanceof IdentityProviderConnection) {
                $query->where('connection_id', $owner->id);

                return;
            }

            $query->where('membership_id', $owner->id)->orWhere(fn (Builder $query) => $query
                ->where('connection_id', $owner->connection_id)
                ->where('identity_id', $owner->identity_id)
                ->where('mode', IdentityProviderOidcSession::MODE_SELF_LINK));
        });

        return IdentityProviderOidcSessionQuery::whereInvalidatable($query)->update([
            'status' => IdentityProviderOidcSession::STATUS_ERROR,
            'exchange_token_hash' => null,
            'browser_token_hash' => null,
            'exchange_expires_at' => null,
            'verified_account' => null,
            'resolved_at' => now(),
        ]);
    }

    /**
     * @param IdentityProviderConnection|IdentityProviderMembership $owner
     * @throws Throwable
     * @return void
     */
    public function revokeProxies(IdentityProviderConnection|IdentityProviderMembership $owner): void
    {
        IdentityProxy::whereIn('id', IdentityProviderProxyBinding::select('identity_proxy_id')
            ->where($owner instanceof IdentityProviderConnection ? 'connection_id' : 'membership_id', $owner->id))
            ->get()
            ->each(fn (IdentityProxy $proxy) => $proxy->deactivateBySession(false));
    }
}
