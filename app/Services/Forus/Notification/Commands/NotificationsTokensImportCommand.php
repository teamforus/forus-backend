<?php

namespace App\Services\Forus\Notification\Commands;

use Illuminate\Console\Command;
use NotificationChannels\Apn\ApnFeedback;
use NotificationChannels\Apn\FeedbackService;

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

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle() {
        $csvPath = $this->argument('csv_path');
        $notificationService = resolve('forus.services.notification');

        if (!file_exists($csvPath)) {
            $this->error(sprintf("File '%s' doesn't exists!", $csvPath));
            exit();
        }

        $tokens = [];

        if (($h = fopen("{$csvPath}", "r")) !== FALSE) {
            while (($data = fgetcsv($h, 1000, ",")) !== FALSE) {
                array_push($tokens, $data);
            }

            fclose($h);
        }

        if (count($tokens) < 2) {
            $this->error(sprintf("File '%s': csv is empty or contains ony the header!", $csvPath));
            exit();
        }

        $header = array_first($tokens);

        if ($header[0] != 'type' || $header[1] != 'identity_address' || $header[2] != 'token') {
            $this->error(sprintf("File '%s': wrong format!", $csvPath));
            $this->info("Correct format: 'type,identity_address,token'");
            exit();
        }

        $countSuccess = 0;
        $countError = 0;

        $tokens = array_slice($tokens, 1);

        foreach ($tokens as $tokenRowKey => $tokenRow) {
            list($type, $identity_address, $token) = $tokenRow;

            try {
                $notificationService->storeNotificationToken($identity_address, $type, $token);
                $countSuccess++;
            } catch (\Exception $exception) {
                $this->error(sprintf(
                    'Could not import a token, line %s',
                    $tokenRowKey + 2
                ));

                $this->error(sprintf('The error: %s', $exception->getMessage()));
                $countError++;
            }
        }

        $this->line(sprintf(
            "%s rows imported, %s rows failed.",
            $countSuccess,
            $countError
        ));
    }
}
