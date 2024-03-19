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
}
