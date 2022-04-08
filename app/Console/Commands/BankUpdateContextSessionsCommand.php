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

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws \bunq\Exception\BunqException
     */
    public function handle(): void
    {
        /** @var BankConnection[] $bankConnections */
        $bankConnections = BankConnection::whereHas('bank', function(Builder $builder) {
            $builder->where('key', 'bunq');
        })->whereState(BankConnection::STATE_ACTIVE)->get();

        foreach ($bankConnections as $bankConnection) {
            try {
                $bankConnection->updateContext($bankConnection->makeNewContext());
            } catch (BunqException $exception) {
                logger()->error($exception->getMessage() . "\n" . $exception->getTraceAsString());
            }
        }
    }
}
