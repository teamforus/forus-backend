<?php

namespace App\Console\Commands;

use App\Events\Funds\FundEndedEvent;
use App\Events\Funds\FundExpiringEvent;
use App\Events\Funds\FundStartedEvent;
use App\Models\Fund;
use App\Scopes\Builders\FundQuery;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class FundsUpdateStateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.funds:update-state';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update fund state by the start/end date';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            $funds = Fund::where(function (Builder $builder) {
                FundQuery::whereIsConfiguredByForus($builder);
            })->whereDate('start_date', '<=', now())->get();

            foreach ($funds as $fund) {
                if ($fund->isPaused() && $fund->start_date->startOfDay()->isPast()) {
                    FundStartedEvent::dispatch($fund->changeState(Fund::STATE_ACTIVE));
                }

                $expirationNotified = $fund->logs()->where('event', Fund::EVENT_FUND_EXPIRING)->exists();
                $isTimeToNotify = $fund->end_date->clone()->subDays(14)->isPast();

                if (!$expirationNotified && !$fund->isClosed() && $isTimeToNotify) {
                    FundExpiringEvent::dispatch($fund);
                }

                if (!$fund->isClosed() && $fund->end_date->clone()->addDay()->isPast()) {
                    FundEndedEvent::dispatch($fund->changeState(Fund::STATE_CLOSED));
                }
            }
        } catch (Throwable $e) {
            logger()->error($e->getMessage());
        }
    }
}
