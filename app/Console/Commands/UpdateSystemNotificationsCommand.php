<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use SystemNotificationsTableSeeder;

class UpdateSystemNotificationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:update-system-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update system notification list.';

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws \Exception
     */
    public function handle(): void
    {
        (new SystemNotificationsTableSeeder())->run();
        echo "System notifications updated!\n";
    }
}
