<?php

namespace App\Console\Commands;

use App\Models\BankConnection;
use App\Models\VoucherTransactionBulk;
use App\Services\BankService\Models\Bank;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class BankVoucherTransactionBulksUpdateStateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bank:bulks-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch bank api to update bulk transactions state.';
}
