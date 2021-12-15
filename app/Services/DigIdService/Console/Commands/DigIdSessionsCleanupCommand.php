<?php

namespace App\Services\DigIdService\Console\Commands;

use App\Services\DigIdService\Models\DigIdSession;
use Illuminate\Console\Command;

class DigIdSessionsCleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'digid:session-clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change state state and remove expired sessions.';

    /**
     * Execute the console command.
     *
     * @return void
     * @throws \Exception
     */
    public function handle(): void
    {
        $olderThan = now()->subSeconds(DigIdSession::SESSION_EXPIRATION_TIME);

        DigIdSession::where(
            'created_at', '<', $olderThan
        )->whereNotIn('state', [
            DigIdSession::STATE_ERROR,
            DigIdSession::STATE_EXPIRED,
            DigIdSession::STATE_AUTHORIZED,
        ])->update([
            'state' => DigIdSession::STATE_EXPIRED,
        ]);

        DigIdSession::where(
            'created_at', '<', $olderThan
        )->delete();
    }
}
