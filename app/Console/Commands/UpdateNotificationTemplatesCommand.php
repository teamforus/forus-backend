<?php

namespace App\Console\Commands;

use Database\Seeders\NotificationTemplatesTableSeeder;
use Illuminate\Console\Command;
use Throwable;

class UpdateNotificationTemplatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:update-templates
                            {--force : Do not ask for confirmation before updating.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update notification templates.';

    /**
     * Execute the console command.
     *
     * @throws Throwable
     * @return void
     */
    public function handle(): void
    {
        (new NotificationTemplatesTableSeeder())->execute($this, (bool) $this->option('force'));
    }
}
