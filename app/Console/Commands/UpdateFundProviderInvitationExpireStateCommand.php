<?php

namespace App\Console\Commands;

use App\Models\FundProviderInvitation;
use Illuminate\Console\Command;

class UpdateFundProviderInvitationExpireStateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.funds.provider_invitations:check-expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update fund provider invitations state if expired.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        FundProviderInvitation::where([
            'state' => FundProviderInvitation::STATE_PENDING
        ])->get()->each(function(FundProviderInvitation $invitation) {
            if ($invitation->expired) {
                $invitation->update([
                    'state' => FundProviderInvitation::STATE_EXPIRED
                ]);
            }
        });
    }
}
