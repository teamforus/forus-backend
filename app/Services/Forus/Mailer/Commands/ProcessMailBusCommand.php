<?php

namespace App\Services\Forus\Mailer\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

use App\Services\Forus\Mailer\Models\MailJob;
use App\Services\Forus\Mailer\MailerRepository;

class ProcessMailBusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.services.mailer:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process mail bus queue.';

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
     *
     * @return void
     */
    public function handle() {

        try {
            $mailBusRepository = new MailerRepository();

            $time = time();
            
            while($time + 60 > time()) {
                /** @var MailJob $mail */
                while($mail = MailJob::query()->where('state', 'pending')->first()) {
                    $mailBusRepository->sendMail($mail);

                    if ($time + 60 < time())
                        break;
                }

                sleep(1);
            }
        } catch (\Exception $e) {
            
        }
    }
}
