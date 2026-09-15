<?php

namespace App\Services\IdentityProviderService\Commands;

use App\Services\IdentityProviderService\Exceptions\IdentityProviderErrorCode;
use App\Services\IdentityProviderService\Models\IdentityProviderAdminSession;
use App\Services\IdentityProviderService\Models\IdentityProviderOidcSession;
use App\Services\IdentityProviderService\Queries\IdentityProviderAdminSessionQuery;
use App\Services\IdentityProviderService\Queries\IdentityProviderOidcSessionQuery;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;

class CleanupIdentityProviderSessions extends Command
{
    protected $signature = 'identity-provider:sessions-clean';
    protected $description = 'Expire and remove identity-provider sessions.';

    /**
     * @return int
     */
    public function handle(): int
    {
        $now = Carbon::now();
        $retentionCutoff = $now->copy()->subDays(max(1, (int) Config::get('identity_providers.session_retention_days')));

        $expiredOidc = $this->expireOidcSessions($now) + $this->expireLinkCompletions($now);
        $expiredAdmin = $this->expireAdminSessions($now);
        $deletedOidc = $this->deleteOldOidcSessions($retentionCutoff);
        $deletedAdmin = $this->deleteOldAdminSessions($retentionCutoff);

        $this->info(sprintf(
            'Expired %d OIDC and %d administration sessions. Removed %d OIDC and %d administration sessions.',
            $expiredOidc,
            $expiredAdmin,
            $deletedOidc,
            $deletedAdmin,
        ));

        return self::SUCCESS;
    }

    /**
     * @param Carbon $now
     * @return int
     */
    protected function expireOidcSessions(Carbon $now): int
    {
        $sessions = IdentityProviderOidcSession::where('expires_at', '<=', $now);

        return IdentityProviderOidcSessionQuery::wherePending($sessions)->update([
            'status' => IdentityProviderOidcSession::STATUS_EXPIRED,
        ]);
    }

    /**
     * @param Carbon $now
     * @return int
     */
    protected function expireAdminSessions(Carbon $now): int
    {
        $sessions = IdentityProviderAdminSession::where('expires_at', '<=', $now);

        return IdentityProviderAdminSessionQuery::whereUnfinished($sessions)->update([
            'status' => IdentityProviderAdminSession::STATUS_EXPIRED,
            'error_code' => IdentityProviderErrorCode::SESSION_EXPIRED,
        ]);
    }

    /**
     * @param Carbon $now
     * @return int
     */
    protected function expireLinkCompletions(Carbon $now): int
    {
        return IdentityProviderOidcSession::where('mode', IdentityProviderOidcSession::MODE_SELF_LINK)
            ->whereNotNull('verified_account')
            ->where(fn (Builder $query) => $query->where('expires_at', '<=', $now)->orWhere('exchange_expires_at', '<=', $now))
            ->update([
                'status' => IdentityProviderOidcSession::STATUS_EXPIRED,
                'verified_account' => null,
                'exchange_token_hash' => null,
                'browser_token_hash' => null,
            ]);
    }

    /**
     * @param Carbon $cutoff
     * @return int
     */
    protected function deleteOldOidcSessions(Carbon $cutoff): int
    {
        return IdentityProviderOidcSessionQuery::wherePrunable(IdentityProviderOidcSession::query(), $cutoff)->delete();
    }

    /**
     * @param Carbon $cutoff
     * @return int
     */
    protected function deleteOldAdminSessions(Carbon $cutoff): int
    {
        return IdentityProviderAdminSessionQuery::wherePrunable(IdentityProviderAdminSession::query(), $cutoff)->delete();
    }
}
