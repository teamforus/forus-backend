<?php

namespace App\Services\WalletService\Commands;

use App\Services\WalletService\Models\WalletSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class WalletSessionsCleanupCommand extends Command
{
    protected $signature = 'wallets:session-clean';
    protected $description = 'Expire and remove Wallet sessions.';

    /**
     * @return int
     */
    public function handle(): int
    {
        $expirationSeconds = Config::get('openid.session_expiration_seconds');
        $softDeleteDays = Config::get('openid.session_soft_delete_after_days');
        $hardDeleteDays = Config::get('openid.session_hard_delete_after_days');

        if ($expirationSeconds <= 0 || $softDeleteDays <= 0) {
            $this->error('Session expiration and soft deletion durations must be positive.');

            return self::FAILURE;
        }

        if ($hardDeleteDays !== null && $hardDeleteDays <= 0) {
            $this->error('Hard deletion duration must be positive or null.');

            return self::FAILURE;
        }

        WalletSession::query()
            ->where('created_at', '<', now()->subSeconds($expirationSeconds))
            ->where('session_state', WalletSession::STATE_PENDING)
            ->update([
                'session_state' => WalletSession::STATE_EXPIRED,
                'browser_token_hash' => null,
                'code_verifier' => null,
            ]);

        WalletSession::query()
            ->where('created_at', '<', now()->subDays($softDeleteDays))
            ->whereIn('session_state', WalletSession::TERMINAL_STATES)
            ->delete();

        if ($hardDeleteDays !== null) {
            WalletSession::withTrashed()
                ->where('created_at', '<', now()->subDays($hardDeleteDays))
                ->whereIn('session_state', WalletSession::TERMINAL_STATES)
                ->forceDelete();
        }

        return self::SUCCESS;
    }
}
