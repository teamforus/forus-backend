<?php

namespace App\Console\Commands;

use Database\Seeders\NotificationTemplatesTableSeeder;
use Illuminate\Console\Command;

class UpdateNotificationTemplatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:update-templates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update notification templates.';


    /**
     * Execute the console command.
     *
     * @return void
     * @throws \Throwable
     */
    public function handle(): void
    {
        (new NotificationTemplatesTableSeeder())->run();
        echo "Templates updated!\n";
    }
}
