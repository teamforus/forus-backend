<?php

namespace App\Services\OpenIdService\Console\Commands;

use App\Services\OpenIdService\Models\OpenIdSession;
use Illuminate\Console\Command;

class OpenIdSessionsCleanupCommand extends Command
{
    protected $signature = 'openid:session-clean';
    protected $description = 'Expire and remove OpenID sessions.';

    public function handle(): void
    {
        OpenIdSession::query()
            ->where('created_at', '<', now()->subSeconds(OpenIdSession::SESSION_EXPIRATION_TIME))
            ->where('session_state', OpenIdSession::STATE_PENDING)
            ->update([
                'session_state' => OpenIdSession::STATE_EXPIRED,
            ]);

        OpenIdSession::query()
            ->where('created_at', '<', now()->subSeconds(
                OpenIdSession::SESSION_EXPIRATION_TIME + OpenIdSession::SESSION_RETENTION_TIME
            ))
            ->whereIn('session_state', OpenIdSession::TERMINAL_STATES)
            ->delete();
    }
}
