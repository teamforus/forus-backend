<?php

namespace App\Console\Commands;

use App\Exports\PhysicalCardRequestsExport;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Writer\Exception;

class ExportPhysicalCardsRequestsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.funds.physical_card_requests:export
                            {--disc=local : target export disk.}
                            {--fund_id= : fund id to process.}
                            {--date= : export requests from given date.}
                            {--export_path= : path where to save the .csv.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export physical car requests to .csv file.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $disc = $this->getOption('disc', 'local');

        if ($disc === 'ftp_physical_cards' &&
            !config('filesystems.disks.ftp_physical_cards.host')) {
            $this->alert("Physical card request ftp not configured.");
            exit();
        }

        $date = $this->getOption('date') ?: now()->subDay()->format('Y-m-d');
        $path = $this->option('export_path') . $date . '.csv';
        $exporter = new PhysicalCardRequestsExport($this->getOption('fund_id'), $date);

        try {
            resolve('excel')->store($exporter, $path, $disc);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * @param string $argument
     * @param null $default
     * @return array|string|null
     */
    protected function getOption(string $argument, $default = null) {
        return $this->hasOption($argument) ? $this->option($argument) : $default;
    }
}
