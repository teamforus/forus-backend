<?php

namespace App\Console\Commands;

use App\Exports\PhysicalCardRequestsExport;
use PhpOffice\PhpSpreadsheet\Writer\Exception;

class ExportPhysicalCardsRequestsCommand extends BaseCommand
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
}
