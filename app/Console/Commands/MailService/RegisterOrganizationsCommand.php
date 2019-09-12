<?php

namespace App\Console\Commands\MailService;

use App\Models\Organization;
use Illuminate\Console\Command;

class RegisterOrganizationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.notifications:sync_organizations_emails {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize all organization emails into the mail service.';

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
     *
     * @return void
     */
    public function handle()
    {
        $mailService = resolve('forus.services.notification');
        $organizations = Organization::all();
        $message = sprintf(
            "You are about to register %s organization(s) " .
            "into the mail service, do you want to continue?",
            $organizations->count()
        );

        if (!$this->option('force')) {
            if (!$this->confirm($message)) {
                return;
            }
        }

        $this->info('Done.');
    }
}
