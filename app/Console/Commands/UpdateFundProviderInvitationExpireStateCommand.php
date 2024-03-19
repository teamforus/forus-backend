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
}
