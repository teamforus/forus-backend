<?php

namespace App\Services\Forus\Notification\Commands;

use Illuminate\Console\Command;

class NotificationsTokensImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.notifications:token-import {csv_path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import apn and fcm tokens from csv file.';
}
