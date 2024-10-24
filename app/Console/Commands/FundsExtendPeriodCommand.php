<?php

namespace App\Console\Commands;

use App\Models\Fund;
use App\Models\FundPeriod;
use Illuminate\Console\Command;

class FundsExtendPeriodCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.funds:extend-periods';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extend fund using pre-configured fund_periods.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $now = now();

        FundPeriod::query()
            ->where('state', '!=', FundPeriod::STATE_ENDED)
            ->whereDate('end_date', '<', $now->format('Y-m-d'))
            ->get()
            ->each(fn (FundPeriod $period) => $period->setEnded());

        FundPeriod::query()
            ->where('state', FundPeriod::STATE_PENDING)
            ->whereDate('start_date', '<=', $now->format('Y-m-d'))
            ->whereDate('end_date', '>=', $now->format('Y-m-d'))
            ->whereRelation('fund', 'state', Fund::STATE_CLOSED)
            ->get()
            ->each(fn (FundPeriod $period) => $period->activate());
    }
}
