<?php

namespace App\Console\Commands;

use App\Models\BankConnection;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class BankUpdateContextSessionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bank:update-context-sessions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update sessions for bunq contexts.';
}
