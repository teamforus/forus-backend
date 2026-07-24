<?php

namespace App\Console\Commands\IConnect;

use App\Console\Commands\BaseCommand;
use App\Console\Commands\IConnect\Traits\FundRequestIConnectCli;
use App\Console\Commands\IConnect\Traits\PrevalidationIConnectCli;
use App\Models\Prevalidation;
use Throwable;

class IConnectCliCommand extends BaseCommand
{
    use FundRequestIConnectCli;
    use PrevalidationIConnectCli;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'iconnect:cli';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update prevalidation request records from IConnect and sync prevalidations';

    /**
     * @throws Throwable
     * @return void
     */
    public function handle(): void
    {
        $this->askAction();
    }

    /**
     * @return array
     */
    protected function askActionList(): array
    {
        return [
            '### Prevalidation:',
            '[1] Update pending prevalidations.',
            '[2] Update used prevalidations.',
            '### Fund requests:',
            '[3] Update fund requests.',
            '### Exit:',
            '[4] Exit',
        ];
    }

    /**
     * @throws Throwable
     * @return void
     */
    protected function askAction(): void
    {
        $this->resetStats();
        $this->printHeader('Select next action:');
        $this->printList($this->askActionList());
        $action = $this->ask('Please select next step:', 1);

        switch ($action) {
            case 1: $this->updatePrevalidations(Prevalidation::STATE_PENDING);
                break;
            case 2: $this->updatePrevalidations(Prevalidation::STATE_USED);
                break;
            case 3: $this->updateFundRequests();
                break;
            case 4: $this->exit();
                // no break
            default: $this->printText("Invalid input!\nPlease try again:\n");
        }

        $this->askAction();
    }
}
